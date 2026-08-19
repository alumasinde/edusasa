<?php

declare(strict_types=1);

namespace App\Core\Middleware;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Tenant;
use Modules\Platform\Services\SchoolEntitlementService;

/**
 * Resolves the school tenant from the current hostname.
 *
 * A school may be configured with either a full custom domain or a short
 * subdomain (for example "nairobbihigh" for nairobihigh.albatechsolutions.co.ke).
 * The session fallback keeps shared-domain/admin flows working as well.
 */
final class TenantResolver
{
    public function __construct(
        private readonly Database $db,
        private readonly SchoolEntitlementService $entitlements,
    ) {}

    public function handle(Request $request, \Closure $next): Response
    {
        if (Tenant::current() !== null) {
            return $next($request);
        }

        $host = strtolower(explode(':', $request->host())[0]);
        $subdomain = $this->extractSubdomain($host);
        $school = null;

        if ($subdomain !== null) {
            $school = $this->findSchoolByHostname($host, $subdomain);
        } else {
            $school = $this->findSchoolByHostname($host, null);
        }

        if ($school === null && Session::has('resolved_school_id')) {
            $school = $this->db->selectOne(
                'SELECT * FROM schools WHERE id = :id AND deleted_at IS NULL LIMIT 1',
                ['id' => Session::get('resolved_school_id')]
            );
        }

        if ($school === null) {
            return Response::view('errors.tenant-not-found', ['host' => $host], 404);
        }

        if (($school['status'] ?? 'active') === 'suspended') {
            return Response::view('errors.tenant-suspended', ['school' => $school], 403);
        }

        $branding = [];
        if (!empty($school['branding_json'])) {
            $decoded = json_decode((string) $school['branding_json'], true);
            if (is_array($decoded)) {
                $branding = $decoded;
            }
        }

        $access = $this->entitlements->resolve((int) $school['id']);
        $features = $access['features'] ?? [];

        Tenant::set(Tenant::fromRow(
            $school,
            $branding,
            $features,
            $features
        ));

        Session::set('resolved_school_id', (int) $school['id']);

        return $next($request);
    }

    private function findSchoolByHostname(string $host, ?string $subdomain): ?array
    {
        if ($subdomain !== null) {
            return $this->db->selectOne(
                'SELECT * FROM schools
                 WHERE deleted_at IS NULL
                   AND status = :status
                   AND (domain = :domain OR subdomain = :subdomain OR subdomain = :full_host)
                 LIMIT 1',
                [
                    'status' => 'active',
                    'domain' => $host,
                    'subdomain' => $subdomain,
                    'full_host' => $host,
                ]
            );
        }

        return $this->db->selectOne(
            'SELECT * FROM schools
             WHERE deleted_at IS NULL
               AND status = :status
               AND domain = :domain
             LIMIT 1',
            [
                'status' => 'active',
                'domain' => $host,
            ]
        );
    }

    private function extractSubdomain(string $host): ?string
    {
        $parts = explode('.', $host);
        if (count($parts) < 3) {
            return null;
        }

        $subdomain = $parts[0];
        return $subdomain !== '' && $subdomain !== 'www' ? $subdomain : null;
    }
}
