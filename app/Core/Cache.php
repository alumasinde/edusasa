<?php

declare(strict_types=1);

namespace App\Core;

/**
 * File-based cache, namespaced by school_id so no tenant can read
 * another's cached data. Swappable for Redis later behind the same
 * interface if load requires it.
 */
class Cache
{
    private static function dir(): string
    {
        $tenantId = Tenant::current()?->id ?? 'platform';
        $dir = dirname(__DIR__, 2) . "/storage/cache/{$tenantId}";

        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        return $dir;
    }

    private static function path(string $key): string
    {
        return self::dir() . '/' . hash('sha256', $key) . '.cache';
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $file = self::path($key);

        if (!is_file($file)) {
            return $default;
        }

        $payload = unserialize((string) file_get_contents($file), ['allowed_classes' => false]);

        if (!is_array($payload) || ($payload['expires_at'] !== null && $payload['expires_at'] < time())) {
            @unlink($file);
            return $default;
        }

        return $payload['value'];
    }

    public static function put(string $key, mixed $value, ?int $ttlSeconds = null): void
    {
        $payload = [
            'value' => $value,
            'expires_at' => $ttlSeconds === null ? null : time() + $ttlSeconds,
        ];

        file_put_contents(self::path($key), serialize($payload));
    }

    public static function remember(string $key, ?int $ttlSeconds, \Closure $resolver): mixed
    {
        $cached = self::get($key, '__MISS__');

        if ($cached !== '__MISS__') {
            return $cached;
        }

        $value = $resolver();
        self::put($key, $value, $ttlSeconds);

        return $value;
    }

    public static function forget(string $key): void
    {
        @unlink(self::path($key));
    }
}
