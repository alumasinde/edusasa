<?php

declare(strict_types=1);

namespace App\Core;

final class Tenant
{
    private static ?self $current = null;

    private function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $subdomain = '',
        public readonly string $domain = '',
        public readonly string $code = '',
        public readonly string $status = 'active',
        public readonly array $branding = [],
        public readonly array $moduleAccess = [],
        public readonly array $planModuleAccess = [],
        public readonly string $admissionNoPrefix = 'ADM',
        public readonly int $admissionNoPadding = 4,
        public readonly string $employeeNoPrefix = 'EMP',
        public readonly int $employeeNoPadding = 4,
    ) {}

    public static function fromRow(array $row, array $branding = [], array $moduleAccess = [], array $planModuleAccess = []): self
    {
        $settings = [];
        if (!empty($row['settings_json'])) {
            $decoded = json_decode((string) $row['settings_json'], true);
            if (is_array($decoded)) $settings = $decoded;
        }
        $settings = array_replace($settings, $branding);
        return new self(
            (int) $row['id'],
            (string) $row['name'],
            (string) ($row['subdomain'] ?? ''),
            (string) ($row['domain'] ?? ''),
            (string) ($row['school_code'] ?? $row['code'] ?? ''),
            (string) ($row['status'] ?? 'active'),
            $settings,
            $moduleAccess,
            $planModuleAccess,
            (string) ($settings['admission_no_prefix'] ?? 'ADM'),
            max(1, (int) ($settings['admission_no_padding'] ?? 4)),
            (string) ($settings['employee_no_prefix'] ?? 'EMP'),
            max(1, (int) ($settings['employee_no_padding'] ?? 4)),
        );
    }

    public static function set(self $tenant): void { self::$current = $tenant; }
    public static function current(): ?self { return self::$current; }
    public static function id(): int
    {
        if (self::$current === null) throw new TenantNotResolvedException('No tenant resolved.');
        return self::$current->id;
    }
    public static function hasModule(string $key): bool { return self::$current !== null && !empty(self::$current->moduleAccess[$key]); }
    public static function planIncludesModule(string $key): bool { return self::$current !== null && !empty(self::$current->planModuleAccess[$key]); }
    public static function clear(): void { self::$current = null; }
    public function storagePath(string $suffix = ''): string
    {
        $base = dirname(__DIR__, 2).'/storage/schools/'.$this->id;
        return $suffix === '' ? $base : $base.'/'.ltrim($suffix, '/');
    }
}
