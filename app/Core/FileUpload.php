<?php

declare(strict_types=1);

namespace App\Core;

class FileUpload
{
    private const DEFAULT_MAX_BYTES = 5 * 1024 * 1024; // 5MB

    /**
     * @param array{name:string,type:string,tmp_name:string,error:int,size:int} $file
     * @param array<int, string> $allowedExtensions
     * @return string Relative path under the tenant's storage root.
     */
    public static function store(
        array $file,
        string $subdirectory,
        array $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'],
        int $maxBytes = self::DEFAULT_MAX_BYTES,
    ): string {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Upload failed with error code ' . $file['error']);
        }

        if ($file['size'] > $maxBytes) {
            throw new \RuntimeException('File exceeds maximum allowed size.');
        }

        $extension = strtolower((string) pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExtensions = array_values(array_unique(array_map('strtolower', $allowedExtensions)));

        if (!in_array($extension, $allowedExtensions, true)) {
            throw new \RuntimeException("File type .{$extension} is not permitted.");
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        $allowedMimes = [
            'jpg' => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'png' => ['image/png'],
            'pdf' => ['application/pdf'],
            'doc' => ['application/msword'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        ];

        if (!isset($allowedMimes[$extension]) || !in_array($mime, $allowedMimes[$extension], true)) {
            throw new \RuntimeException('Uploaded file content does not match the permitted file type.');
        }

        $tenant = Tenant::current() ?? throw new TenantNotResolvedException();
        $targetDir = $tenant->storagePath($subdirectory);

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0775, true);
        }

        $filename = bin2hex(random_bytes(16)) . '.' . $extension;
        $targetPath = $targetDir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            throw new \RuntimeException('Could not move uploaded file.');
        }

        return "{$subdirectory}/{$filename}";
    }

    public static function delete(string $relativePath): bool
    {
        $tenant = Tenant::current() ?? throw new TenantNotResolvedException();
        $fullPath = $tenant->storagePath($relativePath);

        return is_file($fullPath) && unlink($fullPath);
    }
}
