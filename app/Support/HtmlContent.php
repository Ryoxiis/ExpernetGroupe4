<?php

declare(strict_types=1);

namespace App\Support;

/** Render imported text blocks without trusting source HTML or URL protocols. */
final class HtmlContent
{
    private array $routes;
    private array $sourceMap;

    public function __construct(array $routes, array $sourceMap = [])
    {
        $this->routes = $routes;
        $this->sourceMap = $sourceMap;
    }

    public function render(array $blocks): string
    {
        $output = '';
        $listOpen = false;

        foreach ($blocks as $block) {
            $tag = $block['tag'];
            if ($tag === 'anchor') {
                $id = $block['id'] ?? '';
                if (is_string($id) && strlen($id) <= 101
                    && Text::containsOnly($id[0] ?? '', Text::LETTERS)
                    && Text::containsOnly($id, Text::LETTERS . Text::DIGITS . '_-')) {
                    $output .= '<span id="' . \e($block['id']) . '" class="source-anchor"></span>';
                }
                continue;
            }
            if ($tag === 'table') {
                if ($listOpen) {
                    $output .= '</ul>';
                    $listOpen = false;
                }
                $output .= '<div class="source-table"><table><tbody>';
                foreach ($block['rows'] ?? [] as $row) {
                    $output .= '<tr>';
                    foreach ($row as $cell) {
                        $output .= '<td>' . \e($cell) . '</td>';
                    }
                    $output .= '</tr>';
                }
                $output .= '</tbody></table></div>';
                continue;
            }
            if ($tag === 'h1') {
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
        usort($segments, function (array $first, array $second): int {
            if ($first['position'] < $second['position']) {
                return -1;
            }
            if ($first['position'] > $second['position']) {
                return 1;
            }
            return 0;
        });
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

    public function safeUrl(string $href): ?string
    {
        if (Text::hasControlCharacters($href, true)) {
            return null;
        }
        $scheme = strtolower(parse_url($href, PHP_URL_SCHEME) ?: '');
        if (!in_array($scheme, ['http', 'https', 'mailto', 'tel'], true)) {
            return null;
        }
        $host = strtolower(parse_url($href, PHP_URL_HOST) ?: '');
        if (in_array($host, ['expernet.re', 'expernet-campus.re'], true)) {
            $source = 'https://' . $host . (parse_url($href, PHP_URL_PATH) ?: '/');
            $fragment = parse_url($href, PHP_URL_FRAGMENT);
            if (isset($this->sourceMap[$source])) {
                return \url($this->sourceMap[$source]) . ($fragment ? '#' . rawurlencode($fragment) : '');
            }
        }
        if ($host === 'expernet.re') {
            $path = '/' . trim(parse_url($href, PHP_URL_PATH) ?: '', '/') . '/';
            $path = $path === '//' ? '/' : $path;
            if (isset($this->routes[$path])) {
                return \url($path);
            }
        }
        return $href;
    }
}
