<?php

declare(strict_types=1);

namespace Modules\Finance\Controllers;

use App\Core\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Tenant;
use Modules\Finance\Services\FinanceControlService;
use Throwable;

final class FinanceControlController extends BaseController
{
    public function __construct(private readonly FinanceControlService $service) {}

    public function index(Request $request): Response
    {
        return $this->view('finance.controls',['periods'=>$this->service->periods(Tenant::id()),'log'=>$this->service->controlLog(Tenant::id())]);
    }

    public function createPeriod(Request $request): Response
    {
        try {$this->service->createPeriod(Tenant::id(),(string)$request->input('name'),(string)$request->input('starts_on'),(string)$request->input('ends_on'),$request->userId());Session::flash('success','Finance period created.');}
        catch(Throwable $e){Session::flash('error',$e->getMessage());}
        return Response::redirect('/finance/controls');
    }

    public function lockPeriod(Request $request): Response
    {
        try {$this->service->lockPeriod(Tenant::id(),(int)$request->input('id'),$request->userId());Session::flash('success','Period locked.');}
        catch(Throwable $e){Session::flash('error',$e->getMessage());}
        return Response::redirect('/finance/controls');
    }

    public function closePeriod(Request $request): Response
    {
        try {$this->service->closePeriod(Tenant::id(),(int)$request->input('id'),$request->userId());Session::flash('success','Period closed.');}
        catch(Throwable $e){Session::flash('error',$e->getMessage());}
        return Response::redirect('/finance/controls');
    }

    public function reversePayment(Request $request): Response
    {
        try {$this->service->reversePayment(Tenant::id(),(int)$request->input('payment_id'),(string)$request->input('reason'),$request->userId());Session::flash('success','Payment reversed and affected invoice balances restored.');}
        catch(Throwable $e){Session::flash('error',$e->getMessage());}
        return Response::redirect('/finance/controls');
    }

    public function voidInvoice(Request $request): Response
    {
        try {$this->service->voidInvoice(Tenant::id(),(int)$request->input('invoice_id'),(string)$request->input('reason'),$request->userId());Session::flash('success','Invoice voided.');}
        catch(Throwable $e){Session::flash('error',$e->getMessage());}
        return Response::redirect('/finance/controls');
    }
}
