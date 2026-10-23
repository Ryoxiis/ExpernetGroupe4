<?php

declare(strict_types=1);

use App\Repositories\ContentRepository;
use App\Support\View;

// Apache and CLI must resolve the same configuration, without executing .env.
require_once __DIR__ . '/environment.php';

const PROJECT_ROOT = __DIR__ . '/..';

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $file = PROJECT_ROOT . '/app/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

$config = require PROJECT_ROOT . '/config/app.php';
date_default_timezone_set($config['timezone']);
ini_set('display_errors', '0');

require PROJECT_ROOT . '/app/Support/helpers.php';

return [
    'config' => $config,
    'repository' => new ContentRepository(PROJECT_ROOT . '/data'),
    'view' => new View(PROJECT_ROOT . '/app/Views'),
    'routes' => json_decode(
        file_get_contents(PROJECT_ROOT . '/config/routes.json'),
        true,
        512,
        JSON_THROW_ON_ERROR,
    ),
];
