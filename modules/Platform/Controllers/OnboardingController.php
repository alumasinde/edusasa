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
        try {
            $school = $request->only([
                'name', 'code', 'slug', 'email', 'phone', 'domain', 'timezone'
            ]);
            $plan = trim((string) $request->input('plan'));
            $id = $this->onboarding->start($school, $plan);

            return Response::redirect('/platform/schools?onboarded=' . $id);
        } catch (\Throwable $e) {
            return Response::view('platform.onboarding', [
                'plans' => $this->catalog->plans(false),
                'old' => $request->all(),
                'errors' => [$e->getMessage()],
            ], 422);
        }
    }
}
