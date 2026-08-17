<?php

declare(strict_types=1);

namespace App\Core;

class Config
{
    private static ?Config $instance = null;
    private array $items = [];

    private function __construct(string $configPath)
    {
        $this->loadEnv(dirname($configPath, 2) . '/.env');
        foreach (glob($configPath . '/*.php') ?: [] as $file) {
            $this->items[basename($file, '.php')] = require $file;
        }
    }

    public static function boot(string $configPath): self
    {
        return self::$instance ??= new self($configPath);
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            throw new \RuntimeException('Config::boot() must be called before Config::getInstance().');
        }
        return self::$instance;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = self::getInstance()->items;
        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }
        return $value;
    }

    private function loadEnv(string $path): void
    {
        if (!is_file($path)) {
            return;
        }
        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }
            [$name, $value] = explode('=', $line, 2);
            $name = trim($name);
            $value = trim(trim($value), "\"'");
            if (getenv($name) === false) {
                putenv("{$name}={$value}");
            }
            $_ENV[$name] = $_ENV[$name] ?? $value;
        }
    }

    public static function env(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? getenv($key);
        if ($value === false || $value === null) {
            return $default;
        }
        return match (strtolower((string) $value)) {
            'true' => true,
            'false' => false,
            'null' => null,
            default => $value,
        };
    }
}
