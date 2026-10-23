<?php

declare(strict_types=1);
require __DIR__.'/../bootstrap/app.php';

use App\Repositories\PedagogyRepository;
use App\Services\PedagogyService;
use App\Support\Database;
use App\Support\PortalException;

// PDO doubles verify control ordering without connecting to persistent data.
final class RefactorStatement extends PDOStatement
{
    public function __construct(private mixed $row) {}
    public function execute(?array $params = null): bool { return true; }
    public function fetch(int $mode = PDO::FETCH_DEFAULT, int $cursorOrientation = PDO::FETCH_ORI_NEXT, int $cursorOffset = 0): mixed { return $this->row; }
}
final class RefactorDatabase extends PDO
{
    public array $queries = [];
    public array $rows = [];
    public bool $fail = false;
    public function __construct() {}
    public function prepare(string $query, array $options = []): PDOStatement|false
    {
        $this->queries[] = $query;
        return new RefactorStatement(array_shift($this->rows) ?? false);
    }
    public function query(string $query, ?int $fetchMode = null, mixed ...$fetchModeArgs): PDOStatement|false
    {
        $this->queries[] = $query;
        if ($this->fail) throw new PDOException('unavailable');
        return new RefactorStatement(false);
    }
}
function verifyRefactor(bool $condition): void
{
    if (!$condition) throw new RuntimeException('Refactoring regression');
}
$db = new RefactorDatabase();
$service = new PedagogyService(new PedagogyRepository($db), []);
foreach (['createAssignment', 'upload'] as $method) {
    $call = new ReflectionMethod($service, $method);
    foreach (['student', 'trainer', 'admin'] as $role) {
        $db->queries = [];
        $db->rows = [['id' => 4], false];
        try {
            $call->invoke($service, ['id' => 9, 'role' => $role], ['cohort_id' => 4], []);
            throw new RuntimeException('Unauthorized mutation accepted');
        } catch (PortalException $error) {
            // Admin reaches later method-specific validation; students never query.
            if ($role === 'student') {
                verifyRefactor($error->status === 403 && $db->queries === []);
            } elseif ($role === 'trainer') {
                verifyRefactor($error->status === 403 && count($db->queries) === 2);
            } else {
                verifyRefactor(count($db->queries) === 1);
            }
        }
    }
}
$access = new ReflectionMethod($service, 'authorizedCohort');
$db->rows = [['id' => 4], ['user_id' => 9]];
verifyRefactor($access->invoke($service, ['id' => 9, 'role' => 'trainer'], ['cohort_id' => 4], ['trainer', 'admin']) === 4);
$db->queries = [];
Database::checkPortalSchema($db);
verifyRefactor($db->queries === ['SELECT id FROM portal_users LIMIT 1', 'SELECT name FROM portal_locks LIMIT 1']);
$db->fail = true;
try {
    Database::checkPortalSchema($db);
    throw new RuntimeException('Database failure hidden');
} catch (PDOException) {}
echo "Refactorisation : ordre rôle/promotion/affectation et vérification du schéma contrôlés sans base persistante.\n";
