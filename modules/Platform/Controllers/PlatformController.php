<?php

declare(strict_types=1);

namespace Modules\Platform\Controllers;

use App\Core\Request;
use App\Core\Response;
use Modules\Platform\Services\EntitlementAdminService;
use Modules\Platform\Services\EntitlementService;
use Modules\Platform\Services\PlanCatalogService;
use Modules\Platform\Services\SchoolService;

final class PlatformController
{
    public function __construct(
        private readonly SchoolService $schools,
        private readonly EntitlementService $entitlements,
        private readonly PlanCatalogService $catalog,
        private readonly EntitlementAdminService $admin,
    ) {}

    public function dashboard(Request $request, array $params): Response
    {
        $schools=$this->schools->list(); $plans=$this->catalog->plans(false);
        $stats=['Total schools'=>count($schools),'Active schools'=>count(array_filter($schools,static fn(array $s):bool=>($s['status']??'')==='active')),'Pending schools'=>count(array_filter($schools,static fn(array $s):bool=>($s['status']??'')==='pending')),'Suspended schools'=>count(array_filter($schools,static fn(array $s):bool=>($s['status']??'')==='suspended')),'Available plans'=>count($plans)];
        return Response::view('platform.dashboard',compact('stats','plans'));
    }

    public function schoolPage(Request $request,array $params):Response{return Response::view('platform.schools',['schools'=>$this->schools->list(['status'=>$request->input('status'),'search'=>$request->input('search')])]);}
    public function schools(Request $request,array $params):Response{return Response::json(['success'=>true,'data'=>$this->schools->list(['status'=>$request->input('status'),'search'=>$request->input('search')])]);}
    public function createSchool(Request $request,array $params):Response{$id=$this->schools->create($request->only(['name','code','slug','email','phone','domain','timezone']),(string)$request->input('plan','starter'));return Response::json(['success'=>true,'school_id'=>$id],201);}
    public function status(Request $request,array $params):Response{$this->schools->setStatus((int)$params['id'],(string)$request->input('status'));return Response::json(['success'=>true]);}
    public function feature(Request $request,array $params):Response{$id=(int)$params['id'];$code=(string)$params['feature'];return Response::json(['success'=>true,'feature'=>$code,'enabled'=>$this->entitlements->enabled($id,$code),'limits'=>$this->entitlements->limits($id,$code)]);}
    public function plans(Request $request,array $params):Response{return Response::json(['success'=>true,'data'=>$this->catalog->plans($request->input('all')!=='1')]);}
    public function features(Request $request,array $params):Response{return Response::json(['success'=>true,'data'=>$this->catalog->features($request->input('module'))]);}
    public function setPlanFeature(Request $request,array $params):Response{$limits=$request->input('limits');if(is_string($limits)&&$limits!=='')$limits=json_decode($limits,true);$this->admin->setPlanFeature((int)$params['plan'],(int)$params['feature'],(bool)$request->input('enabled'),is_array($limits)?$limits:null);return Response::json(['success'=>true]);}

    public function entitlementPage(Request $request,array $params):Response
    {
        $catalog=$this->admin->catalog();
        return Response::view('platform.entitlements',['plans'=>$catalog['plans'],'features'=>$catalog['features']]);
    }

    public function planPage(Request $request,array $params):Response
    {
        $plan=$this->admin->plan((int)$params['id']);
        if($plan===null)return Response::json(['success'=>false,'message'=>'Plan not found.'],404);
        return Response::view('platform.plan',['plan'=>$plan]);
    }

    public function savePlan(Request $request,array $params):Response
    {
        try{$id=$this->admin->savePlan($request->all(),isset($params['id'])?(int)$params['id']:null);return Response::json(['success'=>true,'id'=>$id]);}
        catch(\Throwable $e){return Response::json(['success'=>false,'message'=>$e->getMessage()],422);}
    }

    public function schoolEntitlementsPage(Request $request,array $params):Response
    {
        $id=(int)$params['id'];
        return Response::view('platform.school-entitlements',['schoolId'=>$id,'schools'=>$this->schools->list(),'features'=>$this->admin->schoolEntitlements($id)]);
    }

    public function saveSchoolOverride(Request $request,array $params):Response
    {
        try{$limits=$request->input('limits');if(is_string($limits)&&$limits!=='')$limits=json_decode($limits,true);$this->admin->setSchoolOverride((int)$params['id'],(int)$request->input('feature_id'),(bool)$request->input('enabled'),is_array($limits)?$limits:null,trim((string)$request->input('reason'))?:null,trim((string)$request->input('expires_at'))?:null);return Response::json(['success'=>true]);}
        catch(\Throwable $e){return Response::json(['success'=>false,'message'=>$e->getMessage()],422);}
    }

    public function removeSchoolOverride(Request $request,array $params):Response{$this->admin->clearSchoolOverride((int)$params['id'],(int)$request->input('feature_id'));return Response::json(['success'=>true]);}
}
