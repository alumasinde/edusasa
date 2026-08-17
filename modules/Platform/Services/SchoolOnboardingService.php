<?php

declare(strict_types=1);

namespace Modules\Platform\Services;

use App\Core\AuditLog;
use App\Core\BaseService;
use InvalidArgumentException;

final class SchoolOnboardingService extends BaseService
{
    public function __construct(
        private readonly SchoolService $schools,
        private readonly PlanCatalogService $catalog,
    ) {}

    /**
     * Creates the school tenant/subscription and a one-time administrator
     * invitation. The raw invitation token is returned once and is never
     * persisted; only its SHA-256 hash is stored.
     */
    public function start(array $school, string $planCode, string $adminEmail): array
    {
        $name = trim((string) ($school['name'] ?? ''));
        $adminEmail = strtolower(trim($adminEmail));
        if ($name === '') throw new InvalidArgumentException('School name is required.');
        if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException('A valid administrator email is required.');

        $plan = $this->catalog->findByCode($planCode);
        if ($plan === null) throw new InvalidArgumentException('Selected plan does not exist or is inactive.');

        // SchoolService already owns the transaction for school + subscription;
        // do not nest another transaction around it.
        $schoolId = $this->schools->create(
            array_intersect_key($school, array_flip(['name','code','slug','email','phone','domain','timezone'])),
            $planCode
        );

        $token = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $this->db()->insert(
            'INSERT INTO school_admin_invitations(school_id,email,token_hash,expires_at)
             VALUES(:school_id,:email,:token_hash,DATE_ADD(NOW(), INTERVAL 48 HOUR))',
            [
                'school_id' => $schoolId,
                'email' => $adminEmail,
                'token_hash' => hash('sha256', $token),
            ]
        );

        AuditLog::record('school.onboarding_started', 'school', $schoolId, null, [
            'plan_code' => $planCode,
            'admin_email' => $adminEmail,
        ]);

        return ['school_id' => $schoolId, 'admin_email' => $adminEmail, 'invitation_token' => $token];
    }
}
