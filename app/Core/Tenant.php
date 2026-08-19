<?php

declare(strict_types=1);

namespace App\Core;

final class Tenant
{
    private static ?Tenant $current = null;

    private function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $subdomain,
        public readonly string $domain,
        public readonly string $code,
        public readonly string $status,
        public readonly array $branding = [],
        public readonly array $moduleAccess = [],
        public readonly array $planModuleAccess = [],
        public readonly string $admissionNoPrefix = 'ADM',
        public readonly int $admissionNoPadding = 4,
        public readonly string $employeeNoPrefix = 'EMP',
        public readonly int $employeeNoPadding = 4,
        public readonly bool $studentPortalShowFees = true,
    ) {}

    public static function fromRow(array $row, array $branding = [], array $moduleAccess = [], array $planModuleAccess = []): self
    {
        return new self(
            id: (int) $row['id'],
            name: (string) $row['name'],
            subdomain: (string) ($row['subdomain'] ?? ''),
            domain: (string) ($row['domain'] ?? ''),
            code: (string) ($row['school_code'] ?? $row['code'] ?? ''),
            status: (string) ($row['status'] ?? 'active'),
            branding: $branding,
            moduleAccess: $moduleAccess,
            planModuleAccess: $planModuleAccess,
            admissionNoPrefix: (string) ($row['admission_no_prefix'] ?? 'ADM'),
            admissionNoPadding: max(1, (int) ($row['admission_no_padding'] ?? 4)),
            employeeNoPrefix: (string) ($row['employee_no_prefix'] ?? 'EMP'),
            employeeNoPadding: max(1, (int) ($row['employee_no_padding'] ?? 4)),
            studentPortalShowFees: (bool) ($row['student_portal_show_fees'] ?? true),
        );
    }

    public static function set(self $tenant): void { self::$current = $tenant; }
    public static function current(): ?self { return self::$current; }
    public static function id(): int
    {
        if (self::$current === null) throw new TenantNotResolvedException('No tenant resolved.');
        return self::$current->id;
    }
    public function hostname(): string { return $this->domain !== '' ? $this->domain : $this->subdomain; }
    public static function hasModule(string $moduleKey): bool { return self::$current !== null && !empty(self::$current->moduleAccess[$moduleKey]); }
    public static function planIncludesModule(string $moduleKey): bool { return self::$current !== null && !empty(self::$current->planModuleAccess[$moduleKey]); }
    public static function clear(): void { self::$current = null; }
    public function storagePath(string $suffix = ''): string
    {
        $base = rtrim(dirname(__DIR__, 2) . '/storage/schools/' . $this->id, '/');
        return $suffix === '' ? $base : $base . '/' . ltrim($suffix, '/');
    }
}
