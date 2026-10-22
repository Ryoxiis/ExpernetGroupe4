<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\ContentRepository;
use App\Support\HtmlContent;
use App\Support\View;

final class PageController
{
    public function __construct(
        private readonly ContentRepository $repository,
        private readonly View $view,
        private readonly array $routes,
    ) {
    }

    public function render(array $page, string $path): string
    {
        $template = $page['type'] === 'page' ? $page['view'] : 'pages/' . $page['type'];
        $data = [
            'page' => $page,
            'path' => $path,
            'repository' => $this->repository,
            'view' => $this->view,
            'html' => new HtmlContent($this->routes),
        ];
        if ($page['type'] === 'course') {
            $data['course'] = $this->repository->course($page['slug']);
        }
        if ($page['type'] === 'article') {
            $data['article'] = $this->repository->article($page['id']);
        }
        $body = $this->view->render($template, $data);
        return $this->view->render('layouts/main', $data + ['body' => $body]);
    }
}
