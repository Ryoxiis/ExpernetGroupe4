<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class PedagogyRepository
{
    public function __construct(public readonly PDO $db)
    {
    }

    // Toutes les requêtes passent par une préparation PDO pour séparer SQL et valeurs.
    private function query(string $sql, array $params): \PDOStatement
    {
        $statement = $this->db->prepare($sql);
        $statement->execute($params);
        return $statement;
    }

    public function all(string $sql, array $params = []): array
    {
        $statement = $this->db->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll();
    }

    public function one(string $sql, array $params = []): ?array
    {
        $row = $this->query($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    public function execute(string $sql, array $params = []): int
    {
        $statement = $this->db->prepare($sql);
        $statement->execute($params);
        return $statement->rowCount();
    }

    public function insert(string $sql, array $params = []): int
    {
        $this->execute($sql, $params);
        return (int) $this->db->lastInsertId();
    }

    public function transaction(callable $work): mixed
    {
        $this->db->beginTransaction();
        try {
            $result = $work();
            $this->db->commit();
            return $result;
        } catch (\Throwable $error) {
            $this->db->rollBack();
            throw $error;
        }
    }

    public function audit(int $actor, string $action, ?int $entity, array $detail = []): void
    {
        $this->execute('INSERT INTO portal_audit(actor_id,action,entity_id,detail,created_at) VALUES(?,?,?,?,UTC_TIMESTAMP())', [$actor, $action, $entity, json_encode($detail, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)]);
    }
}
