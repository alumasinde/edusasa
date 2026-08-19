<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

final class PlatformDatabase
{
    private ?PDO $pdo = null;

    public function __construct(private readonly DatabaseConnectionConfig $config) {}

    public function pdo(): PDO
    {
        if ($this->pdo instanceof PDO) {
            return $this->pdo;
        }

        $this->pdo = new PDO($this->config->dsn(), $this->config->username, $this->config->password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        return $this->pdo;
    }

    public function config(): DatabaseConnectionConfig
    {
        return $this->config;
    }
}
