<?php

declare(strict_types=1);

namespace App\Core;

class Response
{
    private function __construct(
        private readonly int $status,
        private readonly string $body,
        private readonly array $headers = [],
    ) {
    }

    public static function html(string $body, int $status = 200, array $headers = []): self
    {
        $headers['Content-Type'] = 'text/html; charset=UTF-8';

        return new self($status, $body, $headers);
    }

    public static function binary(string $body, int $status = 200, array $headers = []): self
    {
        return new self($status, $body, $headers);
    }

    public static function json(mixed $data, int $status = 200, array $headers = []): self
    {
        $headers['Content-Type'] = 'application/json';

        return new self($status, (string) json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), $headers);
    }

    public static function redirect(string $to, int $status = 302): self
    {
        return new self($status, '', ['Location' => $to]);
    }

    public static function view(string $view, array $data = [], int $status = 200): self
    {
        return self::html(ViewRenderer::render($view, $data), $status);
    }

    /**
     * @param array<int, string> $headers column headers
     * @param array<int, array<int|string, mixed>> $rows each row in the same column order as $headers
     */
    public static function csv(array $headers, array $rows, string $filename): self
    {
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, $headers);
        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }
        rewind($handle);
        $body = (string) stream_get_contents($handle);
        fclose($handle);

        return new self(200, $body, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public static function spreadsheet(array $headers, array $rows, string $filename, string $format = 'xlsx'): self
    {
        $format = \App\Core\Spreadsheet\SpreadsheetWriter::resolveFormat($format);
        $body = \App\Core\Spreadsheet\SpreadsheetWriter::write($headers, $rows, $format);
        $extension = \App\Core\Spreadsheet\SpreadsheetWriter::extension($format);

        return new self(200, $body, [
            'Content-Type' => \App\Core\Spreadsheet\SpreadsheetWriter::mimeType($format),
            'Content-Disposition' => "attachment; filename=\"{$filename}.{$extension}\"",
        ]);
    }

    public function send(): void
    {
        http_response_code($this->status);

        $headers = $this->headers;
        $defaults = [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
        ];

        if (!isset($headers['Content-Security-Policy'])) {
            $defaults['Content-Security-Policy'] = "default-src 'self'; object-src 'none'; base-uri 'self'; frame-ancestors 'self'";
        }

        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            $defaults['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains';
        }

        foreach ($defaults + $headers as $name => $value) {
            header("{$name}: {$value}");
        }

        echo $this->body;
    }
}
