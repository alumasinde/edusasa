<?php

declare(strict_types=1);

namespace Modules\Platform\Controllers;

use App\Core\Request;
use App\Core\Response;
use Modules\Platform\Services\EntitlementService;
use Modules\Platform\Services\PlanCatalogService;
use Modules\Platform\Services\SchoolService;

final class PlatformController
{
    public function __construct(
        private readonly SchoolService $schools,
        private readonly EntitlementService $entitlements,
        private readonly PlanCatalogService $catalog,
    ) {}

    public function schools(Request $request, array $params): Response
    {
        return Response::json(['success'=>true,'data'=>$this->schools->list([
            'status'=>$request->input('status'), 'search'=>$request->input('search')
        ])]);
    }

    public function createSchool(Request $request, array $params): Response
    {
        $id = $this->schools->create($request->only(['name','code','slug','email','phone','domain','timezone']), (string) $request->input('plan','starter'));
        return Response::json(['success'=>true,'school_id'=>$id],201);
    }

    public function status(Request $request, array $params): Response
    {
        $this->schools->setStatus((int)$params['id'], (string)$request->input('status'));
        return Response::json(['success'=>true]);
    }

    public function feature(Request $request, array $params): Response
    {
        $schoolId=(int)$params['id'];
        $code=(string)$params['feature'];
        return Response::json(['success'=>true,'feature'=>$code,'enabled'=>$this->entitlements->enabled($schoolId,$code),'limits'=>$this->entitlements->limits($schoolId,$code)]);
    }

    public function plans(Request $request, array $params): Response
    {
        return Response::json(['success'=>true,'data'=>$this->catalog->plans($request->input('all') !== '1')]);
    }

    public function features(Request $request, array $params): Response
    {
        return Response::json(['success'=>true,'data'=>$this->catalog->features($request->input('module'))]);
    }

    public function setPlanFeature(Request $request, array $params): Response
    {
        $limits=$request->input('limits');
        if (is_string($limits) && $limits !== '') $limits=json_decode($limits,true);
        $this->catalog->setFeature((int)$params['plan'],(int)$params['feature'],(bool)$request->input('enabled'),is_array($limits)?$limits:null);
        return Response::json(['success'=>true]);
    }
}
