<?php

declare(strict_types=1);

namespace Modules\Finance\Controllers;

use App\Core\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use Modules\Finance\Services\FinanceService;
use Modules\Finance\Services\PaymentChannelService;
use RuntimeException;

final class FinanceController extends BaseController
{
    public function __construct(private readonly FinanceService $finance,private readonly PaymentChannelService $channels) {}
    public function index(Request $request): Response { return $this->view('finance.index',['summary'=>$this->finance->dashboard(),'invoices'=>$this->finance->invoices((string)$request->input('search',''))]); }
    public function categories(Request $request): Response { return $this->view('finance.categories',['categories'=>$this->finance->categories()]); }
    public function storeCategory(Request $request): Response { try{$this->finance->createCategory((string)$request->input('name',''),(string)$request->input('code',''),(string)$request->input('description',''));Session::flash('success','Fee category created.');}catch(RuntimeException $e){Session::flash('error',$e->getMessage());}return $this->redirect('/finance/categories'); }
    public function createInvoice(Request $request): Response { return $this->view('finance.invoice_create',['students'=>$this->finance->students((string)$request->input('student_search','')),'categories'=>$this->finance->categories(),'today'=>date('Y-m-d')]); }
    public function storeInvoice(Request $request): Response
    {
        $items=[];$descriptions=(array)$request->input('description',[]);$quantities=(array)$request->input('quantity',[]);$amounts=(array)$request->input('unit_amount',[]);$categories=(array)$request->input('category_id',[]);
        foreach($descriptions as $i=>$description){if(trim((string)$description)===''&&trim((string)($amounts[$i]??''))==='')continue;$items[]=['description'=>(string)$description,'quantity'=>(float)($quantities[$i]??1),'unit_amount'=>(float)($amounts[$i]??0),'category_id'=>(int)($categories[$i]??0)?:null];}
        try{$id=$this->finance->createInvoice(['student_id'=>$request->input('student_id'),'invoice_no'=>$request->input('invoice_no'),'invoice_date'=>$request->input('invoice_date'),'due_date'=>$request->input('due_date'),'discount'=>$request->input('discount'),'items'=>$items]);Session::flash('success','Invoice '.$request->input('invoice_no').' created.');return $this->redirect('/finance?invoice='.$id);}catch(RuntimeException $e){Session::flash('error',$e->getMessage());return $this->redirect('/finance/invoices/create');}
    }
    public function createPayment(Request $request): Response { return $this->view('finance.payment_create',['students'=>$this->finance->students((string)$request->input('student_search','')),'channels'=>$this->channels->active(),'today'=>date('Y-m-d')]); }
    public function students(Request $request): Response { return Response::json(['success'=>true,'data'=>$this->finance->students((string)$request->input('search',''))]); }
    public function storePayment(Request $request): Response
    {
        try{$id=$this->finance->recordPayment(['student_id'=>$request->input('student_id'),'receipt_no'=>$request->input('receipt_no'),'payment_date'=>$request->input('payment_date'),'amount'=>$request->input('amount'),'method'=>$request->input('method'),'reference'=>$request->input('reference'),'payer_name'=>$request->input('payer_name'),'allocations'=>$request->input('allocations',[])]);Session::flash('success','Payment recorded. Receipt #'.$request->input('receipt_no').'.');return $this->redirect('/finance?payment='.$id);}catch(RuntimeException $e){Session::flash('error',$e->getMessage());return $this->redirect('/finance/payments/create');}
    }
}
