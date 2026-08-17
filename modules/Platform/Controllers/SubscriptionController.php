<?php

declare(strict_types=1);

namespace Modules\Platform\Controllers;

use App\Core\Request;
use App\Core\Response;
use Modules\Platform\Services\PlanCatalogService;
use Modules\Platform\Services\SubscriptionService;

final class SubscriptionController
{
    public function __construct(
        private readonly SubscriptionService $subscriptions,
        private readonly PlanCatalogService $plans,
    ) {}

    public function index(Request $request, array $params): Response
    {
        return Response::view('platform.subscriptions', [
            'subscriptions'=>$this->subscriptions->list([
                'status'=>$request->input('status'),
                'search'=>$request->input('search'),
            ]),
            'plans'=>$this->plans->plans(true),
        ]);
    }

    public function changePlan(Request $request, array $params): Response
    {
        $this->subscriptions->changePlan(
            (int)$params['schoolId'],
            (string)$request->input('plan'),
            $request->input('renews_at') ?: null
        );
        return Response::redirect('/platform/subscriptions');
    }

    public function status(Request $request, array $params): Response
    {
        $this->subscriptions->setStatus((int)$params['id'], (string)$request->input('status'));
        return Response::redirect('/platform/subscriptions');
    }
}
