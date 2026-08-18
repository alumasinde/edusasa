<?php

declare(strict_types=1);
namespace Modules\Finance\Controllers;
use App\Core\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Tenant;
use Modules\Finance\Services\FinanceAdjustmentService;
use Throwable;
final class FinanceAdjustmentController extends BaseController
{
    public function __construct(private readonly FinanceAdjustmentService $service) {}
    public function show(Request $request):Response{try{$id=(int)$request->input('id');$invoice=$this->service->invoice(Tenant::id(),$id);if(!$invoice)return Response::html('Invoice not found',404);return $this->view('finance.invoice_adjustments',['invoice'=>$invoice,'adjustments'=>$this->service->adjustments(Tenant::id(),$id)]);}catch(Throwable $e){return Response::html('Unable to load adjustment page',500);}}
    public function adjust(Request $request):Response{try{$this->service->createAdjustment(Tenant::id(),(int)$request->input('invoice_id'),(string)$request->input('type'),(float)$request->input('amount'),(string)$request->input('reason'),(string)$request->input('reference',''),$request->userId());Session::flash('success','Adjustment applied successfully.');}catch(Throwable $e){Session::flash('error',$e->getMessage());}return Response::redirect('/finance/invoices/'.$request->input('invoice_id').'/adjustments');}
    public function refunds(Request $request):Response{return $this->view('finance.refunds',['refunds'=>$this->service->refunds(Tenant::id())]);}
    public function requestRefund(Request $request):Response{try{$this->service->requestRefund(Tenant::id(),(int)$request->input('payment_id'),(float)$request->input('amount'),(string)$request->input('reason'),(string)$request->input('reference',''),$request->userId());Session::flash('success','Refund request created.');}catch(Throwable $e){Session::flash('error',$e->getMessage());}return Response::redirect('/finance/refunds');}
    public function approveRefund(Request $request):Response{try{$this->service->approveRefund(Tenant::id(),(int)$request->input('id'),$request->userId());Session::flash('success','Refund approved.');}catch(Throwable $e){Session::flash('error',$e->getMessage());}return Response::redirect('/finance/refunds');}
    public function processRefund(Request $request):Response{try{$this->service->processRefund(Tenant::id(),(int)$request->input('id'),$request->userId());Session::flash('success','Refund marked as processed and the student account was restored.');}catch(Throwable $e){Session::flash('error',$e->getMessage());}return Response::redirect('/finance/refunds');}
}
