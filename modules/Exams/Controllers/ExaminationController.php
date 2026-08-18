<?php

declare(strict_types=1);

namespace Modules\Exams\Controllers;

use App\Core\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use Modules\Exams\Services\ExaminationSetupService;
use RuntimeException;

final class ExaminationController extends BaseController
{
    public function __construct(private readonly ExaminationSetupService $service) {}
    public function index(Request $request): Response { return $this->view('exams.index',['exams'=>$this->service->exams()]); }
    public function create(Request $request): Response { return $this->view('exams.create',$this->service->formData()+['errors'=>[]]); }
    public function store(Request $request): Response
    {
        try { $id=$this->service->create($request->all(),(array)$request->input('class_ids',[]),(int)(Session::get('user_id')??0)); Session::flash('success','Examination created successfully.'); return $this->redirect('/exams/'.$id); }
        catch(RuntimeException $e){ return $this->view('exams.create',$this->service->formData()+['errors'=>[$e->getMessage()],'old'=>$request->all()],422); }
    }
    public function show(Request $request): Response
    {
        $id=(int)$request->input('id',0);
        $exam=$this->service->exams(); $exam=array_values(array_filter($exam,fn($row)=>(int)$row['id']===$id));
        if(!$exam) return Response::html('Examination not found.',404);
        return $this->view('exams.show',['exam'=>$exam[0]]);
    }
    public function status(Request $request): Response
    {
        try{$this->service->changeStatus((int)$request->input('id',0),(string)$request->input('status',''));Session::flash('success','Examination status updated.');}
        catch(RuntimeException $e){Session::flash('error',$e->getMessage());}
        return $this->redirect('/exams');
    }
}
