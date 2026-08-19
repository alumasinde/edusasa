<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use RuntimeException;

final class TenantDatabaseManager
{
    private ?TenantDatabase $connection = null;

    public function __construct(
        private readonly PlatformDatabase $platform,
        private readonly TenantDatabaseNameGenerator $nameGenerator,
        private readonly array $defaults,
    ) {}

    public function connection(TenantContext $tenant): TenantDatabase
    {
        if ($this->connection instanceof TenantDatabase) {
            if ($this->connection->tenant()->id !== $tenant->id) {
                throw new RuntimeException('A tenant database connection is already bound to this request.');
            }
            return $this->connection;
        }

        $record = $this->findDatabaseRecord($tenant->id);
        if ($record === null) {
            throw new RuntimeException('Tenant database is not registered.');
        }
        if ((string) $record['status'] !== 'ready') {
            throw new RuntimeException('Tenant database is not ready.');
        }

        $database = (string) $record['database_name'];
        $this->assertSafeDatabaseName($database);
        if ($database !== $tenant->database) {
            throw new RuntimeException('Tenant database registry does not match tenant identity.');
        }

        $config = new DatabaseConnectionConfig(
            host: (string) $record['host'],
            port: (int) $record['port'],
            database: $database,
            username: (string) $record['username'],
            password: $this->resolvePassword((string) $record['password_secret_ref']),
            charset: (string) ($this->defaults['charset'] ?? 'utf8mb4'),
        );

        $this->connection = new TenantDatabase($config, $tenant);
        return $this->connection;
    }

    public function disconnect(): void
    {
        $this->connection = null;
    }

    private function findDatabaseRecord(int $schoolId): ?array
    {
        $statement = $this->platform->pdo()->prepare(
            'SELECT school_id,tenant_identifier,database_name,host,port,username,password_secret_ref,status
             FROM school_databases WHERE school_id=:school_id LIMIT 1'
        );
        $statement->execute(['school_id' => $schoolId]);
        $record = $statement->fetch();
        return $record === false ? null : $record;
    }

    private function resolvePassword(string $secretRef): string
    {
        // Secret-manager integration belongs in the provisioning/operations phase.
        // A reference is deliberately stored in the platform DB instead of a raw password.
        $passwords = $this->defaults['passwords'] ?? [];
        if (array_key_exists($secretRef, $passwords)) {
            return (string) $passwords[$secretRef];
        }

        throw new RuntimeException('Tenant database credential is unavailable.');
    }

    private function assertSafeDatabaseName(string $database): void
    {
        if ($database === '' || strlen($database) > 64 || !preg_match('/^[a-z][a-z0-9_]*$/', $database)) {
            throw new RuntimeException('Tenant database identifier is invalid.');
        }
    }
}
