<?php

declare(strict_types=1);

namespace App\Core\Middleware;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Tenant;
use Modules\Platform\Services\SchoolEntitlementService;

final class TenantResolver
{
    public function __construct(
        private readonly Database $db,
        private readonly SchoolEntitlementService $entitlements,
    ) {}

    public function handle(Request $request, \Closure $next): Response
    {
        if (Tenant::current() === null) {
            $host = $request->host();
            $row = $this->db->selectOne(
                'SELECT * FROM schools
                 WHERE deleted_at IS NULL
                   AND status=:status
                   AND (domain=:host OR subdomain=:host)
                 LIMIT 1',
                ['status'=>'active','host'=>$host]
            );

            if ($row !== null) {
                $access = $this->entitlements->resolve((int)$row['id']);
                Tenant::set(Tenant::fromRow(
                    $row,
                    [],
                    $access['features'],
                    $access['features']
                ));
            }
        }

        return $next($request);
    }
}
