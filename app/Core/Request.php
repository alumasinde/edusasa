<?php

declare(strict_types=1);

namespace App\Core;

class Request
{
    private array $query;
    private array $body;
    private array $files;
    private array $server;
    private array $jsonBody = [];

    public function __construct()
    {
        $this->query = $_GET;
        $this->files = $_FILES;
        $this->server = $_SERVER;
        $this->body = $_POST;

        $contentType = $this->server['CONTENT_TYPE'] ?? '';
        if (str_contains($contentType, 'application/json')) {
            $raw = file_get_contents('php://input') ?: '';
            $decoded = json_decode($raw, true);
            $this->jsonBody = is_array($decoded) ? $decoded : [];
        }
    }

    public function method(): string
    {
        $method = strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');

        // Support method spoofing via _method for HTML forms (PUT/PATCH/DELETE).
        if ($method === 'POST' && isset($this->body['_method'])) {
            $method = strtoupper((string) $this->body['_method']);
        }

        return $method;
    }

    public function uri(): string
    {
        $uri = $this->server['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';

        return rtrim($path, '/') === '' ? '/' : rtrim($path, '/');
    }

    public function host(): string
    {
        return strtolower((string) ($this->server['HTTP_HOST'] ?? ''));
    }

    public function ip(): string
    {
        // Do not trust client-controlled forwarding headers by default.
        // Trusted proxy handling can be added explicitly at the edge.
        return (string) ($this->server['REMOTE_ADDR'] ?? '0.0.0.0');
    }

    public function userAgent(): string
    {
        return (string) ($this->server['HTTP_USER_AGENT'] ?? '');
    }

    public function all(): array
    {
        return array_merge($this->query, $this->body, $this->jsonBody);
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    public function only(array $keys): array
    {
        return array_intersect_key($this->all(), array_flip($keys));
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->all());
    }

    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    public function bearerToken(): ?string
    {
        $header = $this->server['HTTP_AUTHORIZATION'] ?? '';

        if (preg_match('/Bearer\s+(\S+)/', $header, $matches)) {
            return $matches[1];
        }

        return null;
    }

    public function isJson(): bool
    {
        return str_contains($this->server['HTTP_ACCEPT'] ?? '', 'application/json')
            || $this->jsonBody !== [];
    }

    public function isApi(): bool
    {
        return str_starts_with($this->uri(), '/api/');
    }
}
