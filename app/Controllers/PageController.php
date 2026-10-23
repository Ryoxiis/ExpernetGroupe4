<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\ContentRepository;
use App\Support\HtmlContent;
use App\Support\View;

final class PageController
{
    private ContentRepository $repository;
    private View $view;
    private array $routes;

    public function __construct(
        ContentRepository $repository,
        View $view,
        array $routes,
    )
    {
        $this->repository = $repository;
        $this->view = $view;
        $this->routes = $routes;
    }

    public function render(array $page, string $path): string
    {
        $template = $page['type'] === 'page' ? $page['view'] : 'pages/' . $page['type'];
        $data = [
            'page' => $page,
            'path' => $path,
            'repository' => $this->repository,
            'view' => $this->view,
            'html' => new HtmlContent($this->routes, $this->repository->sourceMap()),
            'sourcePages' => $this->repository->sourcePages(),
            'routes' => $this->routes,
        ];
        if ($page['type'] === 'course') {
            $data['course'] = $this->repository->course($page['slug']);
        }
        if ($page['type'] === 'article') {
            $data['article'] = $this->repository->article($page['id']);
        }
        $source = $page['source'] ?? array_search($path, $this->repository->sourceMap(), true);
        $data['sourcePage'] = $source ? ($data['sourcePages'][$source] ?? null) : null;
        $body = $this->view->render($template, $data);
        if ($page['type'] !== 'source' && $data['sourcePage']) {
            // Complète la page avec les blocs de la source qui ne sont pas déjà affichés.
            $normalize = function (string $value): string {
                $text = strip_tags($value);
                $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                return preg_replace('/\s+/u', ' ', $text);
            };
            $existing = $normalize($body);
            $missing = [];
            foreach ($data['sourcePage']['blocks'] as $block) {
                if ($block['tag'] === 'anchor') {
                    $present = str_contains($body, 'id="' . ($block['id'] ?? '') . '"');
                } elseif ($block['tag'] === 'table') {
                    $present = false;
                } else {
                    $present = str_contains($existing, $normalize($block['text']));
                }
                if (!$present) {
                    $missing[] = $block;
                }
            }
            if ($missing) {
                $body .= $this->view->render('partials/source-complement', $data + ['missing' => $missing]);
            }
        }
        return $this->view->render('layouts/main', $data + ['body' => $body]);
    }
}
