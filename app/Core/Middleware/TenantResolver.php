<?php

declare(strict_types=1);

namespace App\Core\Middleware;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Tenant;

/**
 * Resolves a school tenant from its subdomain, with a session fallback for
 * shared-domain flows. Platform routes are registered without this middleware.
 */
final class TenantResolver
{
    public function __construct(private readonly Database $db)
    {
    }

    public function handle(Request $request, \Closure $next): Response
    {
        $subdomain = $this->extractSubdomain($request->host());
        $school = null;

        if ($subdomain !== null) {
            $school = $this->db->selectOne(
                'SELECT * FROM schools WHERE subdomain = :subdomain AND deleted_at IS NULL',
                ['subdomain' => $subdomain]
            );
        }

        if ($school === null && Session::has('resolved_school_id')) {
            $school = $this->db->selectOne(
                'SELECT * FROM schools WHERE id = :id AND deleted_at IS NULL',
                ['id' => Session::get('resolved_school_id')]
            );
        }

        if ($school === null) {
            return Response::view('errors.tenant-not-found', [], 404);
        }

        if (($school['status'] ?? '') === 'suspended') {
            return Response::view('errors.tenant-suspended', ['school' => $school], 403);
        }

        $branding = json_decode((string) ($school['branding_json'] ?? '{}'), true) ?: [];

        $planModuleRows = $this->db->select(
            'SELECT module_key FROM plan_modules WHERE plan_id = :plan_id',
            ['plan_id' => $school['plan_id']]
        );
        $planModuleAccess = array_fill_keys(array_column($planModuleRows, 'module_key'), true);

        $moduleRows = $this->db->select(
            'SELECT module_key FROM school_modules WHERE school_id = :id AND is_enabled = 1',
            ['id' => $school['id']]
        );
        $moduleAccess = array_fill_keys(array_column($moduleRows, 'module_key'), true);

        Tenant::set(Tenant::fromRow($school, $branding, $moduleAccess, $planModuleAccess));
        Session::set('resolved_school_id', (int) $school['id']);

        return $next($request);
    }

    private function extractSubdomain(string $host): ?string
    {
        $host = explode(':', $host)[0];
        $parts = explode('.', $host);
        if (count($parts) < 3) return null;
        $sub = $parts[0];
        return $sub === 'www' ? null : $sub;
    }
}
