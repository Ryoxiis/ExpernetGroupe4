<?php

/** Scheduled CLI only: expire messages after 24 hours, including their private files. */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit(1);
}
require dirname(__DIR__) . '/bootstrap/app.php';
try {
    $config = require PROJECT_ROOT . '/config/chat.php';
    $repo = new App\Repositories\PedagogyRepository(App\Support\Database::connect(require PROJECT_ROOT . '/config/database.php'));
    $count = (new App\Services\ChatRetention($repo, $config['storage']))->purge(true);
    echo "Messages expirés supprimés : $count\n";
} catch (Throwable $error) {
    fwrite(STDERR, 'Purge des discussions indisponible : ' . get_class($error) . "\n");
    exit(1);
}
