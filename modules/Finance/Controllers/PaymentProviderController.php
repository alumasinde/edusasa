<?php

declare(strict_types=1);

namespace Modules\Finance\Controllers;

use App\Core\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use Modules\Finance\Services\PaymentProviderService;
use RuntimeException;

final class PaymentProviderController extends BaseController
{
    public function __construct(private readonly PaymentProviderService $service) {}

    public function initiate(Request $request): Response
    {
        try{$result=$this->service->initiate(['student_id'=>$request->input('student_id'),'channel_id'=>$request->input('channel_id'),'invoice_id'=>$request->input('invoice_id'),'amount'=>$request->input('amount'),'phone'=>$request->input('phone')]);if($request->isJson()||$request->isApi())return Response::json(['success'=>true,'data'=>$result]);Session::flash('success',$result['message']);return $this->redirect('/finance/payments/create');}catch(RuntimeException $e){if($request->isJson()||$request->isApi())return Response::json(['success'=>false,'message'=>$e->getMessage()],422);Session::flash('error',$e->getMessage());return $this->redirect('/finance/payments/create');}
    }

    public function callback(Request $request,array $params): Response
    {
        try{$payload=$request->all();$this->service->callback((int)($params['school_id']??0),(string)($params['token']??''),$payload);return Response::json(['ResultCode'=>0,'ResultDesc'=>'Accepted']);}catch(\Throwable $e){return Response::json(['ResultCode'=>1,'ResultDesc'=>'Callback processing failed'],200);}
    }
}
