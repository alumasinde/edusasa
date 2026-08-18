<?php

declare(strict_types=1);

namespace Modules\Exams\Controllers;

use App\Core\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use Modules\Exams\Services\GradingService;
use RuntimeException;

final class GradingController extends BaseController
{
    public function __construct(private readonly GradingService $service) {}

    public function index(Request $request): Response
    {
        try { return $this->view('exams.grading',['data'=>$this->service->pageData((int)$request->input('id',0))]); }
        catch(RuntimeException $e){ return Response::html($e->getMessage(),404); }
    }

    public function createScale(Request $request): Response
    {
        try { $items=(array)$request->input('items',[]);$this->service->createScale((string)$request->input('name',''),(string)$request->input('code',''),$items,(bool)$request->input('is_default',false));Session::flash('success','Grade scale created.'); }
        catch(RuntimeException $e){ Session::flash('error',$e->getMessage()); }
        return $this->redirect('/exams/'.(int)$request->input('id',0).'/grading');
    }

    public function calculate(Request $request): Response
    {
        $examId=(int)$request->input('id',0);try{$this->service->calculate($examId,(int)$request->input('scale_id',0));Session::flash('success','Examination results calculated successfully.');}catch(RuntimeException $e){Session::flash('error',$e->getMessage());}return $this->redirect('/exams/'.$examId.'/grading');
    }
}
