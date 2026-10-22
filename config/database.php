<?php

declare(strict_types=1);

// Read only when a feature actually needs MySQL. No schema or business data is invented here.
return [
    'host' => getenv('DB_HOST') ?: '127.0.0.1',
    'port' => getenv('DB_PORT') ?: '3306',
    'name' => getenv('DB_DATABASE') ?: 'expernet',
    'user' => getenv('DB_USERNAME') ?: 'expernet',
    'password' => getenv('DB_PASSWORD') ?: '',
];
