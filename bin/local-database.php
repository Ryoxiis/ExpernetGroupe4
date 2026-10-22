<?php

declare(strict_types=1);

// Réservé à l'instance locale créée par le lanceur Windows, sur le port 33306.
$root = dirname(__DIR__);
require $root . '/bootstrap/environment.php';
$options = [PDO::ATTR_TIMEOUT => 3, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];

try {
    if (($argv[1] ?? '') === 'stop') {
        $db = new PDO('mysql:host=127.0.0.1;port=33306', 'root', getenv('DB_ROOT_PASSWORD') ?: '', $options);
        $db->exec('SHUTDOWN');
        exit;
    }
    if (is_file($root . '/.env')) {
        // Une configuration existante appartient à l'utilisateur : ne pas l'écraser.
        echo "Configuration .env existante conservée.\n";
        exit;
    }
    $password = bin2hex(random_bytes(24));
    $adminPassword = bin2hex(random_bytes(32));
    $key = bin2hex(random_bytes(32));
    $db = new PDO('mysql:host=127.0.0.1;port=33306;charset=utf8mb4', 'root', '', $options);
    $db->exec('CREATE DATABASE IF NOT EXISTS expernet CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $db->exec("CREATE USER 'expernet'@'127.0.0.1' IDENTIFIED BY '$password'");
    $db->exec("GRANT ALL PRIVILEGES ON expernet.* TO 'expernet'@'127.0.0.1'");
    $env = "APP_ENV=local\nDB_HOST=127.0.0.1\nDB_PORT=33306\nDB_DATABASE=expernet\nDB_USERNAME=expernet\nDB_PASSWORD=$password\nDB_ROOT_PASSWORD=$adminPassword\nAPP_KEY=$key\nPORTAL_DEMO=true\nPORTAL_MFA=false\nCHAT_WS_URL=\nCHAT_ALLOWED_ORIGINS=http://127.0.0.1:8093\nCHAT_REQUIRE_SCAN=false\n";
    if (file_put_contents($root . '/.env', $env) === false) {
        throw new RuntimeException('Impossible de créer .env.');
    }
    $db->exec("ALTER USER 'root'@'localhost' IDENTIFIED BY '$adminPassword'");
    echo "Base locale créée. Secrets conservés uniquement dans .env.\n";
} catch (Throwable $error) {
    fwrite(STDERR, "Initialisation locale impossible : " . $error->getMessage() . "\n");
    exit(1);
}
