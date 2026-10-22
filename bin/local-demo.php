<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap/environment.php';
require __DIR__ . '/../bootstrap/app.php';

$db = App\Support\Database::connect(require PROJECT_ROOT . '/config/database.php');
if ($db->query('SELECT id FROM portal_users LIMIT 1')->fetch()) {
    echo "Comptes existants conservés.\n";
    exit;
}
// La commande existante crée les cinq comptes fictifs et affiche leurs accès temporaires.
$argv = [__FILE__, 'seed-demo'];
require __DIR__ . '/portal.php';
