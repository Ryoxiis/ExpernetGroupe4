<?php
/** Add test accounts and empty discussions from database.sql without resetting the database. */
declare(strict_types=1);

use App\Repositories\PedagogyRepository;
use App\Support\Database;

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require __DIR__ . '/../bootstrap/app.php';
$config = require PROJECT_ROOT . '/config/pedagogy.php';
if (!$config['demo'] || strtolower(getenv('APP_ENV') ?: 'local') === 'production') {
    fwrite(STDERR, "Commande réservée aux tests : PORTAL_DEMO=true et APP_ENV différent de production.\n");
    exit(1);
}
$locked = false;
try {
    $repo = new PedagogyRepository(Database::connect(require PROJECT_ROOT . '/config/database.php'));
    foreach (['003_collaboration.sql', '004_chat_groups.sql'] as $migration) {
        if (!$repo->one('SELECT version FROM schema_migrations WHERE version=?', [$migration])) {
            throw new RuntimeException('Exécutez d’abord : php bin/portal.php migrate');
        }
    }
    $locked = (int) $repo->one("SELECT GET_LOCK('expernet.chat-demo.v1',10) AS locked")['locked'] === 1;
    if (!$locked) {
        throw new RuntimeException('Un chargement est déjà en cours. Réessayez dans quelques instants.');
    }
    // Refuse collisions rather than attaching an unrelated existing account to test groups.
    foreach ($repo->all("SELECT name,role,email FROM portal_users WHERE email REGEXP '^chat[0-9]{2}[.]demo@expernet[.]re$'") as $user) {
        $number = (int) substr($user['email'], 4, 2);
        $role = $number <= 12 ? 'student' : ($number <= 15 ? 'trainer' : 'admin');
        if ($number < 1 || $number > 16 || !str_ends_with($user['name'], 'Démo') || $user['role'] !== $role) {
            throw new RuntimeException('Une adresse de test est déjà utilisée par un autre profil. Aucune donnée modifiée.');
        }
    }
    $sql = file_get_contents(PROJECT_ROOT . '/database.sql');
    $begin = '-- BEGIN EXPERNET CHAT DEMO V1';
    $end = '-- END EXPERNET CHAT DEMO V1';
    if (substr_count($sql, $begin) !== 1 || substr_count($sql, $end) !== 1) {
        throw new RuntimeException('Section des comptes de discussion introuvable dans database.sql.');
    }
    $section = explode($begin, $sql, 2)[1];
    $section = explode($end, $section, 2)[0];
    // Cette section contient uniquement des INSERT, sans point-virgule dans les valeurs.
    $statements = array_filter(array_map('trim', explode(';', $section)));
    $allowedTables = ['portal_users', 'portal_cohorts', 'portal_memberships', 'portal_chat_conversations', 'portal_chat_participants'];
    foreach ($statements as $statement) {
        $prefix = strstr($statement, '(', true);
        $table = str_starts_with($prefix ?: '', 'INSERT INTO ') ? substr($prefix, 12) : '';
        if (!in_array($table, $allowedTables, true)) {
            throw new RuntimeException('La section de test doit uniquement ajouter des comptes et des conversations vides.');
        }
    }
    $repo->transaction(function () use ($repo, $statements): void {
        foreach ($statements as $statement) {
            $repo->execute($statement);
        }
    });
    echo "16 comptes de test disponibles, 2 promotions et 11 conversations prêtes.\n";
    echo "Aucun message prérempli. Les envois passent par la vraie messagerie MySQL.\n";
    echo "Identifiants et parcours : docs/TEST-DISCUSSIONS.md\n";
} catch (Throwable $error) {
    fwrite(STDERR, $error instanceof RuntimeException && !$error instanceof PDOException
        ? $error->getMessage() . "\n"
        : "Chargement impossible. Vérifiez MySQL, la configuration .env et les migrations.\n");
    exit(1);
} finally {
    if ($locked) {
        $repo->one("SELECT RELEASE_LOCK('expernet.chat-demo.v1')");
    }
}
