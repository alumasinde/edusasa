<?php

declare(strict_types=1);

namespace Modules\Teachers\Controllers;

use App\Core\AuditLog;
use App\Core\BaseController;
use App\Core\Request;
use App\Core\Response;
use Modules\Academic\Repositories\SubjectRepository;
use Modules\Teachers\Repositories\TeacherRepository;
use Modules\Teachers\Repositories\TeacherSubjectRepository;

final class TeacherSubjectMatrixController extends BaseController
{
    public function __construct(private readonly TeacherRepository $teachers,private readonly SubjectRepository $subjects,private readonly TeacherSubjectRepository $assignments){}
    public function show(Request $request): Response{return $this->view('teachers.subject-assignments.matrix',['teachers'=>$this->teachers->active(),'subjects'=>$this->subjects->all('name ASC'),'capabilities'=>$this->assignments->capabilityMap()]);}
    public function update(Request $request): Response{$raw=(array)$request->input('capabilities',[]);$checked=[];foreach($raw as $teacherId=>$subjectIds)foreach(array_keys((array)$subjectIds) as $subjectId)$checked[(int)$teacherId][(int)$subjectId]=true;$this->assignments->syncCapabilities($checked);AuditLog::record('teacher_subjects.matrix_updated','teacher_subjects',null,null,['teacher_count'=>count($checked)]);return $this->redirect('/teachers/subject-assignments/matrix','Saved.');}
}
