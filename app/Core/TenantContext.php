<?php

declare(strict_types=1);

namespace App\Core;

final readonly class TenantContext
{
    public function __construct(
        public int $id,
        public string $code,
        public string $name,
        public string $database,
        public string $status,
        public ?string $domain = null,
        public ?string $subdomain = null,
    ) {
        if ($this->id < 1) {
            throw new \InvalidArgumentException('Tenant ID must be positive.');
        }
        if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9_-]{0,79}$/', $this->code)) {
            throw new \InvalidArgumentException('Tenant code is invalid.');
        }
        if (!preg_match('/^[a-z0-9_]+$/', $this->database)) {
            throw new \InvalidArgumentException('Tenant database identifier is invalid.');
        }
    }
}
