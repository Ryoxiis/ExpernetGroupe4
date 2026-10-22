<?php

declare(strict_types=1);

use App\Controllers\PageController;

$app = require __DIR__ . '/../bootstrap/app.php';
$controller = new PageController($app['repository'], $app['view'], $app['routes']);
$normalize = static fn (string $value): string => trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags(str_replace(['</td>', '</th>', '</p>', '</li>'], ' ', $value)), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
$blocks = 0;
$failures = [];
foreach ($app['repository']->sourcePages() as $source => $record) {
    $path = $record['path'];
    if (!isset($app['routes'][$path])) {
        $failures[] = 'Route manquante : ' . $path;
        continue;
    }
    $output = $controller->render($app['routes'][$path], $path);
    $text = $normalize($output);
    foreach ($record['blocks'] as $block) {
        if ($block['tag'] === 'anchor') {
            if (!str_contains($output, 'id="' . e($block['id']) . '"')) {
                $failures[] = 'Ancre absente : ' . $path . '#' . $block['id'];
            }
            continue;
        }
        $blocks++;
        if (!str_contains($text, trim(preg_replace('/\s+/u', ' ', $block['text'])))) {
            $failures[] = $path . ' : ' . excerpt($block['text'], 130);
        }
    }
}
if ($failures) {
    fwrite(STDERR, implode("\n", $failures) . "\n");
    exit(1);
}
echo count($app['repository']->sourcePages()) . " pages de contenu et $blocks blocs du relevé conservés.\n";
