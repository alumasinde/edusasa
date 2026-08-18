<?php

declare(strict_types=1);

namespace Modules\Finance\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Tenant;
use Modules\Finance\Services\PublicPaymentService;
use Throwable;

final class PublicPaymentController
{
    public function __construct(private readonly PublicPaymentService $service) {}
    public function show(Request $request,array $params):Response{try{return Response::view('finance/public_payment',['token'=>(string)$params['token']]+$this->service->invoice((string)$params['token']));}catch(Throwable $e){return Response::view('finance/payment_error',['message'=>$e->getMessage()],400);}}
    public function initiate(Request $request,array $params):Response{try{$result=$this->service->initiate((string)$params['token'],$request->all());if(!empty($result['authorization_url']))return Response::redirect((string)$result['authorization_url']);return Response::view('finance/payment_pending',$result+['token'=>(string)$params['token']]);}catch(Throwable $e){return Response::view('finance/payment_error',['message'=>$e->getMessage()],422);}}
    public function status(Request $request,array $params):Response{try{return Response::json($this->service->status((int)$params['id'],(string)$request->input('token','')));}catch(Throwable $e){return Response::json(['status'=>'error','message'=>$e->getMessage()],404);}}
    public function generateLink(Request $request,array $params):Response{try{$studentId=(int)$request->input('student_id',0);$invoiceId=(int)$params['id'];if($studentId<1||$invoiceId<1)throw new \RuntimeException('Student and invoice are required.');$link='/pay/'.$this->service->createLink(Tenant::id(),$studentId,$invoiceId);return Response::json(['url'=>$link,'expires_in'=>86400]);}catch(Throwable $e){return Response::json(['error'=>$e->getMessage()],422);}}
    public function mpesaCallback(Request $request,array $params):Response{try{$this->service->mpesaCallback((int)$request->input('tx',0),(string)$request->input('token',''),$request->all());return Response::json(['ResultCode'=>0,'ResultDesc'=>'Accepted']);}catch(Throwable $e){return Response::json(['ResultCode'=>1,'ResultDesc'=>'Rejected'],400);}}
    public function paystackWebhook(Request $request,array $params):Response{try{$this->service->paystackWebhook((string)$request->header('X-Paystack-Signature',''),$request->rawBody());return Response::json(['received'=>true]);}catch(Throwable $e){return Response::json(['received'=>false],400);}}
    public function paystackReturn(Request $request,array $params):Response{try{$this->service->paystackReturn((string)$request->input('reference',''));return Response::view('finance/payment_return',['status'=>'processed','message'=>'Paystack has returned the payment result.']);}catch(Throwable $e){return Response::view('finance/payment_error',['message'=>$e->getMessage()],422);}}
    public function pesapalCallback(Request $request,array $params):Response{try{$this->service->pesapalNotification($request->all());return Response::view('finance/payment_return',['status'=>'processed','message'=>'Pesapal has returned the payment result.']);}catch(Throwable $e){return Response::view('finance/payment_error',['message'=>$e->getMessage()],422);}}
    public function pesapalIpn(Request $request,array $params):Response{try{$this->service->pesapalNotification($request->all());return Response::json(['orderNotificationType'=>$request->input('OrderNotificationType','IPNCHANGE'),'orderTrackingId'=>$request->input('OrderTrackingId',''),'orderMerchantReference'=>$request->input('OrderMerchantReference',''),'status'=>200]);}catch(Throwable $e){return Response::json(['status'=>500],500);}}
}
