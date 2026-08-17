<?php

declare(strict_types=1);

namespace Modules\Platform\Services;

use App\Core\Database;
use App\Core\Request;
use App\Core\Session;

final class PlatformAuditService
{
    public function __construct(private readonly Database $db) {}

    public function record(string $action, ?string $resourceType = null, ?int $resourceId = null, ?int $schoolId = null, array $metadata = [], ?Request $request = null): void
    {
        $this->db->insert(
            'INSERT INTO platform_audit_logs(platform_user_id,school_id,action,resource_type,resource_id,metadata_json,ip_address)
             VALUES(:platform_user_id,:school_id,:action,:resource_type,:resource_id,:metadata_json,:ip_address)',
            [
                'platform_user_id'=>Session::get('platform_user_id'),
                'school_id'=>$schoolId,
                'action'=>$action,
                'resource_type'=>$resourceType,
                'resource_id'=>$resourceId,
                'metadata_json'=>$metadata === [] ? null : json_encode($metadata, JSON_THROW_ON_ERROR),
                'ip_address'=>$request?->ip(),
            ]
        );
    }
}
