<?php

declare(strict_types=1);

namespace Modules\Exams\Controllers;

use App\Core\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use Modules\Exams\Services\ExaminationPaperService;
use RuntimeException;

final class ExaminationPaperController extends BaseController
{
    public function __construct(private readonly ExaminationPaperService $service) {}

    public function index(Request $request): Response
    {
        $examId=(int)$request->input('id',0);
        try { return $this->view('exams.papers',['data'=>$this->service->pageData($examId),'errors'=>[]]); }
        catch(RuntimeException $e){ return Response::html($e->getMessage(),404); }
    }

    public function store(Request $request): Response
    {
        $examId=(int)$request->input('id',0);
        try { $this->service->create($examId,$request->all(),(int)(Session::get('user_id')??0)); Session::flash('success','Examination paper added successfully.'); }
        catch(RuntimeException $e){ Session::flash('error',$e->getMessage()); }
        return $this->redirect('/exams/'.$examId.'/papers');
    }

    public function status(Request $request): Response
    {
        $examId=(int)$request->input('id',0);
        try { $this->service->updateStatus((int)$request->input('paper_id',0),(string)$request->input('status','')); Session::flash('success','Paper status updated.'); }
        catch(RuntimeException $e){ Session::flash('error',$e->getMessage()); }
        return $this->redirect('/exams/'.$examId.'/papers');
    }

    public function delete(Request $request): Response
    {
        $examId=(int)$request->input('id',0);
        try { $this->service->delete((int)$request->input('paper_id',0)); Session::flash('success','Paper removed.'); }
        catch(RuntimeException $e){ Session::flash('error',$e->getMessage()); }
        return $this->redirect('/exams/'.$examId.'/papers');
    }
}
