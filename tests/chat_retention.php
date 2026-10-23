<?php

declare(strict_types=1);
require __DIR__.'/../bootstrap/app.php';

use App\Repositories\PedagogyRepository;
use App\Services\ChatRetention;

final class RetentionStatement extends PDOStatement
{
    public function __construct(private RetentionPDO $db, private string $sql) {}
    public function execute(?array $params = null): bool
    {
        $this->db->trace[] = [$this->sql, $params];
        return true;
    }
    public function fetchAll(int $mode = PDO::FETCH_DEFAULT, mixed ...$args): array
    {
        return str_contains($this->sql, 'SELECT id FROM portal_chat_messages')
            ? [['id' => 7], ['id' => 8]] : [['stored_name' => $this->db->name]];
    }
    public function fetch(int $mode = PDO::FETCH_DEFAULT, int $cursorOrientation = PDO::FETCH_ORI_NEXT, int $cursorOffset = 0): mixed
    {
        return $this->db->shared ? ['id' => 9] : false;
    }
    public function rowCount(): int { return 2; }
}
final class RetentionPDO extends PDO
{
    public array $trace = [];
    public bool $committed = false;
    public bool $rolledBack = false;
    public bool $shared = false;
    public string $name;
    public function __construct() { $this->name = str_repeat('a', 48); }
    public function prepare(string $query, array $options = []): PDOStatement|false { return new RetentionStatement($this, $query); }
    public function beginTransaction(): bool { return true; }
    public function commit(): bool { $this->committed = true; return true; }
    public function rollBack(): bool { $this->rolledBack = true; return true; }
}
function checkRetention(bool $ok): void { if (!$ok) throw new RuntimeException('Retention regression'); }
$directory = sys_get_temp_dir().'/expernet-retention-'.bin2hex(random_bytes(5));
mkdir($directory, 0700);
try {
    $db = new RetentionPDO();
    $file = $directory.'/'.$db->name;
    file_put_contents($file, 'expired attachment');
    $service = new ChatRetention(new PedagogyRepository($db), $directory);
    $before = gmdate('Y-m-d H:i:s', time() - 86400);
    checkRetention($service->purge(true) === 2 && !is_file($file) && $db->committed);
    $after = gmdate('Y-m-d H:i:s', time() - 86400);
    checkRetention($db->trace[0][1][0] >= $before && $db->trace[0][1][0] <= $after);
    $deletes = array_values(array_filter($db->trace, static fn ($entry) => str_starts_with($entry[0], 'DELETE')));
    checkRetention(str_contains($deletes[0][0], 'portal_chat_files') && $deletes[0][1] === [7, 8]);
    checkRetention(str_contains($deletes[1][0], 'portal_chat_messages') && $deletes[1][1] === [7, 8]);
    $queries = count($db->trace);
    checkRetention($service->purge() === 0 && count($db->trace) === $queries);
    $db = new RetentionPDO();
    $db->shared = true;
    file_put_contents($file, 'still referenced');
    checkRetention((new ChatRetention(new PedagogyRepository($db), $directory))->purge(true) === 2 && is_file($file));
    $db = new RetentionPDO();
    $db->name = '../outside';
    try {
        (new ChatRetention(new PedagogyRepository($db), $directory))->purge(true);
        throw new LogicException('Unsafe path accepted');
    } catch (RuntimeException) {
        checkRetention($db->rolledBack && !$db->committed && is_file($file));
        checkRetention(!array_filter($db->trace, static fn ($entry) => str_starts_with($entry[0], 'DELETE')));
    }
    echo "Purge : limite UTC de 24 h, fichiers, ordre des suppressions, références partagées, verrou et rollback vérifiés.\n";
} finally {
    foreach (glob($directory.'/*') as $file) unlink($file);
    if (is_file($directory.'/.retention.lock')) unlink($directory.'/.retention.lock');
    rmdir($directory);
}
