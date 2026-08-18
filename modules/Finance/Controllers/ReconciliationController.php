<?php

declare(strict_types=1);

namespace Modules\Finance\Controllers;

use App\Core\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use Modules\Finance\Services\ReconciliationService;
use RuntimeException;

final class ReconciliationController extends BaseController
{
    public function __construct(private readonly ReconciliationService $service) {}
    public function index(Request $request): Response
    {
        $date=(string)$request->input('date',date('Y-m-d'));
        return $this->view('finance.reconciliation',['date'=>$date,'summary'=>$this->service->dashboard($date),'payments'=>$this->service->payments($date,(string)$request->input('method','')?:null)]);
    }
    public function save(Request $request): Response
    {
        try{$this->service->save(['id'=>$request->input('id'),'date'=>$request->input('date'),'method'=>$request->input('method'),'actual_amount'=>$request->input('actual_amount'),'notes'=>$request->input('notes')]);Session::flash('success','Reconciliation saved.');}catch(RuntimeException $e){Session::flash('error',$e->getMessage());}
        return $this->redirect('/finance/reconciliation?date='.urlencode((string)$request->input('date',date('Y-m-d'))));
    }
    public function confirm(Request $request): Response
    {
        try{$this->service->reconcile((int)$request->input('id'));Session::flash('success','Reconciliation confirmed.');}catch(RuntimeException $e){Session::flash('error',$e->getMessage());}
        return $this->redirect('/finance/reconciliation?date='.urlencode((string)$request->input('date',date('Y-m-d'))));
    }
}
