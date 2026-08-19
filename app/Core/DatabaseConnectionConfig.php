<?php

declare(strict_types=1);

namespace App\Core;

final readonly class DatabaseConnectionConfig
{
    public function __construct(
        public string $host,
        public int $port,
        public string $database,
        public string $username,
        public string $password,
        public string $charset = 'utf8mb4',
    ) {
        if ($this->host === '' || $this->database === '' || $this->username === '') {
            throw new \InvalidArgumentException('Database connection configuration is incomplete.');
        }
        if ($this->port < 1 || $this->port > 65535) {
            throw new \InvalidArgumentException('Database port is invalid.');
        }
    }

    public function dsn(): string
    {
        return sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $this->host,
            $this->port,
            $this->database,
            $this->charset,
        );
    }
}
