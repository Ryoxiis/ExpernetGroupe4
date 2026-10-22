<?php

declare(strict_types=1);

use App\Controllers\PageController;
use App\Controllers\PedagogyController;

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');

try {
    $app = require __DIR__ . '/../bootstrap/app.php';
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    $path = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
    $base = $app['config']['base_path'];
    if ($base !== '' && ($path === $base || str_starts_with($path, $base . '/'))) {
        $path = substr($path, strlen($base)) ?: '/';
    }
    if (preg_match('/[\x00-\x1F\\\\]/', $path) || preg_match('~(?:^|/)\.\.(?:/|$)~', $path)) {
        http_response_code(400);
        echo 'Chemin invalide.';
        exit;
    }
    $path = '/' . trim($path, '/') . '/';
    $path = $path === '//' ? '/' : $path;

    if (str_starts_with($path, '/api/discussions/')) {
        (new \App\Controllers\ChatController())->handle($path, $method);
        exit;
    }

    $pedagogy = new PedagogyController($app['view']);
    if ($pedagogy->handles($path)) {
        if ($method === 'HEAD') { ob_start(); }
        $pedagogy->handle($path, $method);
        if ($method === 'HEAD') { ob_end_clean(); }
        exit;
    }

    if (!in_array($method, ['GET', 'HEAD'], true)) {
        header('Allow: GET, HEAD');
        http_response_code(405);
        echo 'Méthode non autorisée. Aucune donnée n’a été enregistrée.';
        exit;
    }
    $api = [
        '/api/formations/' => static fn (): array => $app['repository']->courseSummaries(),
        '/api/sessions/' => static fn (): array => $app['repository']->sessions(),
        '/api/palettes/' => static fn (): array => $app['repository']->palettes(),
    ];
    if (isset($api[$path])) {
        header('Content-Type: application/json; charset=utf-8');
        if ($method !== 'HEAD') {
            echo json_encode($api[$path](), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        exit;
    }

    header('Content-Type: text/html; charset=utf-8');
    if (!isset($app['routes'][$path])) {
        http_response_code(404);
        echo $app['view']->render('pages/error', ['status' => 404, 'message' => 'Cette page est introuvable.']);
        exit;
    }
    $controller = new PageController($app['repository'], $app['view'], $app['routes']);
    $response = $controller->render($app['routes'][$path], $path);
    if ($method !== 'HEAD') {
        echo $response;
    }
} catch (Throwable $error) {
    error_log((string) $error);
    http_response_code(500);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html lang="fr"><meta charset="utf-8"><title>Service indisponible</title><h1>Le service est temporairement indisponible.</h1></html>';
}
