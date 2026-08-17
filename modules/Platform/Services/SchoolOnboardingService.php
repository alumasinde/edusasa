<?php

declare(strict_types=1);

namespace Modules\Platform\Services;

use App\Core\AuditLog;
use App\Core\BaseService;
use App\Core\Database;
use App\Core\Tenant;
use InvalidArgumentException;

final class SchoolOnboardingService extends BaseService
{
    public function __construct(
        private readonly SchoolService $schools,
        private readonly PlanCatalogService $catalog,
    ) {}

    /**
     * Create a school and its initial subscription atomically. The caller can
     * complete administrator provisioning through the platform user service.
     */
    public function start(array $school, string $planCode): int
    {
        $name = trim((string) ($school['name'] ?? ''));
        if ($name === '') {
            throw new InvalidArgumentException('School name is required.');
        }

        $plan = $this->catalog->findByCode($planCode);
        if ($plan === null) {
            throw new InvalidArgumentException('Selected plan does not exist.');
        }

        $db = Database::getInstance();
        $id = 0;
        $db->transaction(function () use ($school, $planCode, &$id): void {
            $id = $this->schools->create(
                array_intersect_key($school, array_flip([
                    'name', 'code', 'slug', 'email', 'phone', 'domain', 'timezone'
                ])),
                $planCode
            );
        });

        AuditLog::record('school.onboarding_started', 'school', $id, null, [
            'plan_code' => $planCode,
        ]);

        return $id;
    }
}
