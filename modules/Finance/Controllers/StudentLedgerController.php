<?php

declare(strict_types=1);

namespace Modules\Finance\Controllers;

use App\Core\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use Modules\Finance\Services\StudentLedgerService;
use RuntimeException;

final class StudentLedgerController extends BaseController
{
    public function __construct(private readonly StudentLedgerService $service) {}

    public function ledger(Request $request): Response
    {
        try {
            $id=(int)$request->input('id');
            $data=$this->service->ledger($id,(string)$request->input('from',''),(string)$request->input('to',''));
            return $this->view('finance.student_ledger',$data);
        } catch(RuntimeException $e) {
            Session::flash('error',$e->getMessage()); return $this->redirect('/finance');
        }
    }

    public function statement(Request $request): Response
    {
        try {
            $id=(int)$request->input('id');
            $data=$this->service->statement($id,(string)$request->input('from',''),(string)$request->input('to',''));
            return $this->view('finance.student_statement',$data);
        } catch(RuntimeException $e) {
            Session::flash('error',$e->getMessage()); return $this->redirect('/finance');
        }
    }
}
