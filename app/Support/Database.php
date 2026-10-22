<?php

declare(strict_types=1);

namespace App\Support;

use PDO;

/** Optional PDO connection, unused by the public snapshot prototype. */
final class Database
{
    public static function connect(array $config): PDO
    {
        return new PDO(
            sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $config['host'], $config['port'], $config['name']),
            $config['user'],
            $config['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false],
        );
    }
}
