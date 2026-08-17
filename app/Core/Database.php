<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOStatement;

class Database
{
    private static ?Database $instance = null;
    private PDO $pdo;

    private function __construct()
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            Config::env('DB_HOST', '127.0.0.1'),
            Config::env('DB_PORT', '3306'),
            Config::env('DB_DATABASE', 'edusasa'),
            Config::env('DB_CHARSET', 'utf8mb4')
        );

        $this->pdo = new PDO($dsn, (string) Config::env('DB_USERNAME', 'root'), (string) Config::env('DB_PASSWORD', ''), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false,
        ]);
    }

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    public function query(string $sql, array $params = []): PDOStatement
    {
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);
        return $statement;
    }

    public function select(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    public function selectOne(string $sql, array $params = []): ?array
    {
        $row = $this->query($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    /** Backward-compatible semantic alias used by services. */
    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->select($sql, $params);
    }

    /** Backward-compatible semantic alias used by services. */
    public function fetchOne(string $sql, array $params = []): ?array
    {
        return $this->selectOne($sql, $params);
    }

    public function insert(string $sql, array $params = []): string
    {
        $this->query($sql, $params);
        return $this->pdo->lastInsertId();
    }

    public function execute(string $sql, array $params = []): int
    {
        return $this->query($sql, $params)->rowCount();
    }

    /** Execute an UPDATE statement. */
    public function update(string $sql, array $params = []): int
    {
        return $this->execute($sql, $params);
    }

    /** Execute a DELETE statement. */
    public function delete(string $sql, array $params = []): int
    {
        return $this->execute($sql, $params);
    }

    public function transaction(callable $callback): mixed
    {
        $this->pdo->beginTransaction();
        try {
            $result = $callback($this);
            $this->pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }
}
