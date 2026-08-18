<?php

declare(strict_types=1);

namespace Modules\Teachers\Controllers;

use App\Core\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Core\ValidationException;
use Modules\Academic\Repositories\DepartmentRepository;
use Modules\Academic\Repositories\SchoolClassRepository;
use Modules\Academic\Repositories\SubjectRepository;
use Modules\Teachers\Repositories\TeacherSubjectRepository;
use Modules\Teachers\Services\TeacherCrudService;
use Modules\Teachers\Services\TeacherSubjectService;

final class TeacherController extends BaseController
{
    public function __construct(private readonly TeacherCrudService $service, private readonly DepartmentRepository $departments, private readonly SchoolClassRepository $classes, private readonly SubjectRepository $subjects, private readonly TeacherSubjectRepository $assignments, private readonly TeacherSubjectService $assignmentService) {}
    public function index(Request $request): Response { $filters=['q'=>(string)$request->input('q',''),'department_id'=>(string)$request->input('department_id',''),'status'=>(string)$request->input('status','')];$page=max(1,(int)$request->input('page',1));$total=$this->service->count($filters);return $this->view('teachers.index',['staff'=>$this->service->list($filters,$page,25),'filters'=>$filters,'departments'=>$this->departments->all('name ASC'),'page'=>$page,'totalPages'=>(int)max(1,ceil($total/25)),'total'=>$total]); }
    public function export(Request $request): Response { $filters=['q'=>(string)$request->input('q',''),'department_id'=>(string)$request->input('department_id',''),'status'=>(string)$request->input('status','')];$rows=$this->service->list($filters,1,100000);return Response::csv(['Employee No','First Name','Last Name','Email','Phone','Department','Status'],array_map(static fn($t)=>[$t['employee_no'],$t['first_name'],$t['last_name'],$t['email']??'',$t['phone']??'',$t['department_name']??'',$t['status']],$rows),'teachers.csv'); }
    public function create(Request $request): Response { return $this->view('teachers.form',['teacher'=>null,'departments'=>$this->departments->all('name ASC'),'errors'=>[]]); }
    public function store(Request $request): Response { try{$data=$this->validate($request,['first_name'=>'required|max:100','last_name'=>'required|max:100','employee_no'=>'required|max:80','gender'=>'in:male,female','phone'=>'max:40','email'=>'email','department_id'=>'exists:departments,id','joined_on'=>'date']);$id=$this->service->create($data);return $this->redirect('/teachers/'.$id,'Teacher added.');}catch(ValidationException $e){return $this->view('teachers.form',['teacher'=>null,'departments'=>$this->departments->all('name ASC'),'errors'=>$e->errors()],422);} }
    public function show(Request $request,array $params): Response { $id=(int)$params['id'];try{$teacher=$this->service->find($id);}catch(\RuntimeException){return $this->notFound();}return $this->view('teachers.show',['teacher'=>$teacher,'assignments'=>$this->assignments->forTeacher($id),'subjects'=>$this->subjects->all('name ASC'),'classes'=>$this->classes->all('sequence ASC'),'assignmentErrors'=>[]]); }
    public function edit(Request $request,array $params): Response { try{$teacher=$this->service->find((int)$params['id']);}catch(\RuntimeException){return $this->notFound();}return $this->view('teachers.form',['teacher'=>$teacher,'departments'=>$this->departments->all('name ASC'),'errors'=>[]]); }
    public function update(Request $request,array $params): Response { $id=(int)$params['id'];try{$data=$this->validate($request,['first_name'=>'required|max:100','last_name'=>'required|max:100','gender'=>'in:male,female','phone'=>'max:40','email'=>'email','department_id'=>'exists:departments,id','joined_on'=>'date']);$this->service->update($id,$data);return $this->redirect('/teachers/'.$id,'Teacher updated.');}catch(ValidationException $e){return $this->view('teachers.form',['teacher'=>$this->service->find($id),'departments'=>$this->departments->all('name ASC'),'errors'=>$e->errors()],422);} }
    public function setStatus(Request $request,array $params): Response { $id=(int)$params['id'];$data=$this->validate($request,['status'=>'required|in:active,inactive,suspended,left']);$this->service->setStatus($id,(string)$data['status']);return $this->redirect('/teachers/'.$id,'Status updated.'); }
    public function destroy(Request $request,array $params): Response { $this->service->delete((int)$params['id']);return $this->redirect('/teachers','Teacher removed.'); }
    public function assignSubject(Request $request,array $params): Response { $id=(int)$params['id'];try{$data=$this->validate($request,['subject_id'=>'required|exists:subjects,id','class_id'=>'exists:classes,id']);$classId=($data['class_id']??'')!==''?(int)$data['class_id']:null;$periods=trim((string)$request->input('periods_per_week',''));$periods=$periods!==''?(int)$periods:null;if($classId!==null&&($periods===null||$periods<1||$periods>40))throw new ValidationException(['periods_per_week'=>['Enter 1–40 periods/week for a class-specific assignment.']]);$this->assignmentService->assign($id,(int)$data['subject_id'],$classId,$periods);return $this->redirect('/teachers/'.$id.'#subjects','Subject assigned.');}catch(ValidationException $e){return $this->view('teachers.show',['teacher'=>$this->service->find($id),'assignments'=>$this->assignments->forTeacher($id),'subjects'=>$this->subjects->all('name ASC'),'classes'=>$this->classes->all('sequence ASC'),'assignmentErrors'=>$e->errors()],422);} }
    public function unassignSubject(Request $request,array $params): Response { $this->assignmentService->unassign((int)$params['assignmentId']);return $this->redirect('/teachers/'.(int)$params['id'].'#subjects','Assignment removed.'); }
}
