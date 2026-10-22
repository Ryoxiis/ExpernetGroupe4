<?php

declare(strict_types=1);

namespace App\Support;

/** Render imported text blocks without trusting source HTML or URL protocols. */
final class HtmlContent
{
    public function __construct(private readonly array $routes)
    {
    }

    public function render(array $blocks): string
    {
        $output = '';
        $listOpen = false;

        foreach ($blocks as $block) {
            $tag = $block['tag'];
            if (in_array($tag, ['h1', 'table'], true)) {
                continue;
            }
            $text = $this->inline($block['text'], $block['links'] ?? []);
            if ($tag === 'li') {
                if (!$listOpen) {
                    $output .= '<ul>';
                    $listOpen = true;
                }
                $output .= '<li>' . $text . '</li>';
                continue;
            }
            if ($listOpen) {
                $output .= '</ul>';
                $listOpen = false;
            }
            $tag = in_array($tag, ['h2', 'h3', 'h4', 'h5', 'blockquote'], true) ? $tag : 'p';
            $output .= '<' . $tag . '>' . $text . '</' . $tag . '>';
        }

        return $output . ($listOpen ? '</ul>' : '');
    }

    private function inline(string $text, array $links): string
    {
        $segments = [];
        foreach ($links as $link) {
            $label = $link['text'] ?? '';
            $href = $this->safeUrl($link['href'] ?? '');
            $position = $label !== '' ? strpos($text, $label) : false;
            if ($position === false || $href === null) {
                continue;
            }
            $segments[] = ['position' => $position, 'length' => strlen($label), 'label' => $label, 'url' => $href];
        }
        usort($segments, static fn (array $a, array $b): int => $a['position'] <=> $b['position']);
        $offset = 0;
        $html = '';
        foreach ($segments as $segment) {
            if ($segment['position'] < $offset) {
                continue;
            }
            $html .= \e(substr($text, $offset, $segment['position'] - $offset));
            $html .= '<a href="' . \e($segment['url']) . '">' . \e($segment['label']) . '</a>';
            $offset = $segment['position'] + $segment['length'];
        }
        return $html . \e(substr($text, $offset));
    }

    private function safeUrl(string $href): ?string
    {
        if (preg_match('/[\x00-\x20]/', $href)) {
            return null;
        }
        $scheme = strtolower(parse_url($href, PHP_URL_SCHEME) ?: '');
        if (!in_array($scheme, ['http', 'https', 'mailto', 'tel'], true)) {
            return null;
        }
        if (parse_url($href, PHP_URL_HOST) === 'expernet.re') {
            $path = '/' . trim(parse_url($href, PHP_URL_PATH) ?: '', '/') . '/';
            $path = $path === '//' ? '/' : $path;
            if (isset($this->routes[$path])) {
                return \url($path);
            }
        }
        return $href;
    }
}
