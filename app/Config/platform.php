<?php

declare(strict_types=1);

namespace App\Config;

use App\Core\Config;

final class PlatformConfig
{
    public static function host(): string
    {
        return strtolower(trim((string) Config::env('PLATFORM_HOST', 'admin.edusasa.co.ke')));
    }

    public static function requireHost(string $host): bool
    {
        $host = strtolower(trim(explode(':', $host, 2)[0]));
        $allowed = array_filter(array_map('trim', explode(',', (string) Config::env('PLATFORM_HOSTS', self::host()))));
        return in_array($host, $allowed, true);
    }
}
