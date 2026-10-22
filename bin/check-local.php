<?php

declare(strict_types=1);

// Le lanceur affiche un diagnostic simple avant de démarrer les services.
$missing = [];
foreach (['pdo_mysql', 'mbstring', 'fileinfo', 'sodium', 'zip'] as $extension) {
    if (!extension_loaded($extension)) {
        $missing[] = $extension;
    }
}
if (PHP_VERSION_ID < 80200 || $missing) {
    fwrite(STDERR, 'PHP 8.2+ requis. Extensions manquantes : ' . implode(', ', $missing) . PHP_EOL);
    exit(1);
}
echo 'PHP ' . PHP_VERSION . ' : extensions du portail disponibles.' . PHP_EOL;
