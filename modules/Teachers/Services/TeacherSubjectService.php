<?php

declare(strict_types=1);

namespace Modules\Teachers\Services;

use App\Core\AuditLog;
use App\Core\BaseService;
use App\Core\ValidationException;
use Modules\Teachers\Repositories\TeacherSubjectRepository;

final class TeacherSubjectService extends BaseService
{
    public function __construct(private readonly TeacherSubjectRepository $teacherSubjects) {}
    public function assign(int $teacherId,int $subjectId,?int $classId=null,?int $periodsPerWeek=null,?int $streamId=null):int { if($this->teacherSubjects->exists($teacherId,$subjectId,$classId,$streamId))throw new ValidationException(['subject_id'=>['This subject is already assigned to this teacher for that class.']]);$id=(int)$this->teacherSubjects->create(['school_id'=>\App\Core\Tenant::id(),'teacher_id'=>$teacherId,'subject_id'=>$subjectId,'class_id'=>$classId,'stream_id'=>$streamId,'periods_per_week'=>$periodsPerWeek]);AuditLog::record('teacher_subject.assigned','teacher_subjects',$id,null,['teacher_id'=>$teacherId,'subject_id'=>$subjectId,'class_id'=>$classId,'stream_id'=>$streamId,'periods_per_week'=>$periodsPerWeek]);return$id; }
    public function unassign(int $id):void{$before=$this->teacherSubjects->find($id);if($before===null)throw new \RuntimeException('Assignment not found.');$this->teacherSubjects->delete($id);AuditLog::record('teacher_subject.unassigned','teacher_subjects',$id,$before,null);}
}
