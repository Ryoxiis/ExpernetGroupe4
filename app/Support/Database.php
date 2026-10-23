<?php

declare(strict_types=1);

namespace App\Support;

use PDO;

/** PDO connection for the private portal; the public catalogue remains independent. */
final class Database
{
    /** Check the portal tables required by both local launchers. */
    public static function checkPortalSchema(PDO $db): void
    {
        $db->query('SELECT id FROM portal_users LIMIT 1');
        $db->query('SELECT name FROM portal_locks LIMIT 1');
    }

    public static function connect(array $config): PDO
    {
        return new PDO(
            sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $config['host'], $config['port'], $config['name']),
            $config['user'],
            $config['password'],
            [PDO::ATTR_TIMEOUT => 3, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false],
        );
    }
}
