<?php

declare(strict_types=1);

namespace Modules\Platform\Controllers;

use App\Core\Request;
use App\Core\Response;
use Modules\Platform\Services\PlanCatalogService;
use Modules\Platform\Services\SchoolOnboardingService;

final class OnboardingController
{
    public function __construct(
        private readonly SchoolOnboardingService $onboarding,
        private readonly PlanCatalogService $catalog,
    ) {}

    public function create(Request $request, array $params): Response
    {
        return Response::view('platform.onboarding', [
            'plans' => $this->catalog->plans(false),
            'old' => [],
            'errors' => [],
        ]);
    }

    public function store(Request $request, array $params): Response
    {
        $old = $request->all();
        try {
            $school = $request->only(['name','code','slug','email','phone','domain','timezone']);
            $plan = trim((string) $request->input('plan'));
            $adminEmail = trim((string) $request->input('admin_email'));
            $result = $this->onboarding->start($school, $plan, $adminEmail);

            return Response::view('platform.onboarding-success', [
                'schoolId' => $result['school_id'],
                'adminEmail' => $result['admin_email'],
                'invitationToken' => $result['invitation_token'],
            ]);
        } catch (\Throwable $e) {
            return Response::view('platform.onboarding', [
                'plans' => $this->catalog->plans(false),
                'old' => $old,
                'errors' => [$e->getMessage()],
            ], 422);
        }
    }
}
