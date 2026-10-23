<?php
/** Verify that reloading test accounts preserves every existing user and message. */
declare(strict_types=1);
require __DIR__ . '/../bootstrap/app.php';
$config = require PROJECT_ROOT . '/config/pedagogy.php';
if (!$config['demo'] || getenv('APP_ENV') === 'production') {
    exit(1);
}
$repo = new App\Repositories\PedagogyRepository(App\Support\Database::connect(require PROJECT_ROOT . '/config/database.php'));
$fingerprint = function () use ($repo): string {
    $rows = [];
    foreach (['portal_users','portal_memberships','portal_chat_conversations','portal_chat_participants','portal_chat_messages','portal_chat_access','portal_chat_policies'] as $table) {
        $rows[$table] = $repo->all('SELECT * FROM '.$table.' ORDER BY 1,2');
    }
    return hash('sha256', serialize($rows));
};
$before = $fingerprint();
$process = proc_open([PHP_BINARY, PROJECT_ROOT.'/bin/seed-chat.php'], [0 => ['pipe','r'], 1 => ['pipe','w'], 2 => ['pipe','w']], $pipes);
if (!is_resource($process)) {
    throw new RuntimeException('Unable to start seeder');
}
fclose($pipes[0]);
$output = stream_get_contents($pipes[1]);
$error = stream_get_contents($pipes[2]);
fclose($pipes[1]);
fclose($pipes[2]);
if (proc_close($process) !== 0 || $fingerprint() !== $before) {
    throw new RuntimeException('Seeder is not idempotent: '.$error);
}
echo "OK : relance sans doublons, mots de passe, permissions et messages existants inchangés.\n";
