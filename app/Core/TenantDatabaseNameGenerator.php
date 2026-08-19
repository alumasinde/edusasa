<?php

declare(strict_types=1);

namespace App\Core;

final class TenantDatabaseNameGenerator
{
    public function generate(string $tenantId): string
    {
        $normalized = strtolower(trim($tenantId));
        if ($normalized === '') {
            throw new \InvalidArgumentException('Tenant identifier is required.');
        }

        $normalized = preg_replace('/[^a-z0-9]+/', '_', $normalized) ?? '';
        $normalized = trim($normalized, '_');
        if ($normalized === '') {
            throw new \InvalidArgumentException('Tenant identifier cannot produce a safe database name.');
        }

        $name = 'edusasa_' . $normalized;
        if (strlen($name) > 64) {
            $name = substr($name, 0, 55) . '_' . substr(hash('sha256', $normalized), 0, 8);
        }

        if (!preg_match('/^[a-z][a-z0-9_]*$/', $name)) {
            throw new \InvalidArgumentException('Generated tenant database name is invalid.');
        }

        return $name;
    }
}
