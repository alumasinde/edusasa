<?php

declare(strict_types=1);

namespace Modules\Platform\Controllers;

use App\Core\Request;
use App\Core\Response;
use Modules\Platform\Services\EntitlementService;
use Modules\Platform\Services\SchoolService;

final class PlatformController
{
    public function __construct(
        private readonly SchoolService $schools,
        private readonly EntitlementService $entitlements,
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
}
