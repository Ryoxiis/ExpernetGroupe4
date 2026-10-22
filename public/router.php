<?php

declare(strict_types=1);

// Router for the PHP development server. Only static public assets bypass the front controller.
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
$base = trim(getenv('APP_BASE_PATH') ?: '', '/');
if ($base !== '' && str_starts_with($path, '/' . $base . '/')) {
    $path = substr($path, strlen($base) + 1);
}
$assets = realpath(__DIR__ . '/assets');
$file = realpath(__DIR__ . $path);
if (
    $assets !== false && $file !== false && is_file($file)
    && str_starts_with($file, $assets . DIRECTORY_SEPARATOR)
    && in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'webp', 'svg', 'woff', 'woff2', 'otf'], true)
) {
    return false;
}
require __DIR__ . '/index.php';
