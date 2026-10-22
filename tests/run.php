<?php

declare(strict_types=1);

use App\Controllers\PageController;
use App\Support\HtmlContent;

$app = require __DIR__ . '/../bootstrap/app.php';
$checks = 0;
function check(bool $condition, string $message): void
{
    global $checks;
    $checks++;
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

check(count($app['routes']) === 211, 'Le nombre de routes a changé.');
check(count($app['repository']->courses()) === 149, 'Le catalogue doit conserver les 149 fiches.');
check(count($app['repository']->articles()) === 28, 'Les 28 articles doivent être conservés.');
check($app['repository']->course('inexistant') === null, 'Un identifiant inconnu ne doit pas retourner une fiche.');
check(str_contains(e('<script>"'), '&lt;script&gt;'), 'Les textes doivent être échappés.');

$html = new HtmlContent($app['routes']);
$unsafe = $html->render([
    ['tag' => 'p', 'text' => '<script>alert(1)</script>', 'links' => []],
    ['tag' => 'p', 'text' => 'Cliquer', 'links' => [['text' => 'Cliquer', 'href' => 'javascript:alert(1)']]],
]);
check(!str_contains($unsafe, '<script>'), 'Le contenu importé ne doit pas devenir du HTML actif.');
check(!str_contains($unsafe, 'href='), 'Le protocole javascript doit être refusé.');
try {
    $app['view']->render('../../config/app');
    throw new RuntimeException('La vue dangereuse a été acceptée.');
} catch (RuntimeException $error) {
    check($error->getMessage() === 'Nom de vue invalide.', 'La traversée de répertoires doit être refusée.');
}

$controller = new PageController($app['repository'], $app['view'], $app['routes']);
foreach ($app['routes'] as $path => $route) {
    $output = $controller->render($route, $path);
    check(substr_count($output, '<h1') === 1, 'Un seul H1 attendu : ' . $path);
    check(str_contains($output, 'assets/css/base.css'), 'Styles absents : ' . $path);
    check(!str_contains($output, '<script src="/catalogue.js'), 'Ancien script global encore chargé.');
}

echo $checks . " contrôles PHP réussis ; 211 routes rendues.\n";
