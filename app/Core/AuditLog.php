<?php

declare(strict_types=1);

namespace App\Core;

final class AuditLog
{
    public static function record(
        string $action,
        ?string $resourceType = null,
        ?int $resourceId = null,
        mixed $before = null,
        mixed $after = null,
    ): void {
        try {
            $db = Database::getInstance();
            $userId = Session::get('user_id');
            $schoolId = Tenant::current()?->id;

            $metadata = [
                'before' => $before,
                'after' => $after,
            ];

            $db->insert(
                'INSERT INTO platform_audit_logs
                    (platform_user_id, school_id, action, resource_type, resource_id, metadata_json, ip_address)
                 VALUES
                    (:platform_user_id, :school_id, :action, :resource_type, :resource_id, :metadata_json, :ip_address)',
                [
                    'platform_user_id' => $userId !== null ? (int) $userId : null,
                    'school_id' => $schoolId,
                    'action' => $action,
                    'resource_type' => $resourceType,
                    'resource_id' => $resourceId,
                    'metadata_json' => json_encode($metadata, JSON_THROW_ON_ERROR),
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                ],
            );
        } catch (\Throwable $e) {
            Logger::error('Audit log write failed', [
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
