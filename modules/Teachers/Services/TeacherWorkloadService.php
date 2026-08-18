<?php

declare(strict_types=1);

namespace Modules\Teachers\Services;

use App\Core\BaseService;
use App\Core\Tenant;

final class TeacherWorkloadService extends BaseService
{
    public function matrix(?int $teacherId=null): array
    {
        $sql='SELECT ts.teacher_id, CONCAT(t.first_name," ",t.last_name) teacher_name, ts.subject_id, sub.name subject_name, ts.class_id, c.name class_name, ts.stream_id, str.name stream_name, COALESCE(ts.periods_per_week,1) periods_per_week FROM teacher_subjects ts JOIN teachers t ON t.id=ts.teacher_id AND t.school_id=:school_id JOIN subjects sub ON sub.id=ts.subject_id LEFT JOIN classes c ON c.id=ts.class_id LEFT JOIN streams str ON str.id=ts.stream_id WHERE ts.school_id=:school_id';$p=['school_id'=>Tenant::id()];if($teacherId!==null){$sql.=' AND ts.teacher_id=:teacher_id';$p['teacher_id']=$teacherId;}$sql.=' ORDER BY t.first_name,t.last_name,sub.name';$rows=$this->db()->select($sql,$p);$matrix=[];foreach($rows as $row){$id=(int)$row['teacher_id'];$matrix[$id]??=['teacher_id'=>$id,'teacher_name'=>$row['teacher_name'],'total_periods_per_week'=>0,'assignments'=>[]];$periods=max(0,(int)$row['periods_per_week']);$matrix[$id]['total_periods_per_week']+=$periods;$matrix[$id]['assignments'][]=['subject_id'=>(int)$row['subject_id'],'subject_name'=>$row['subject_name'],'class_id'=>$row['class_id']!==null?(int)$row['class_id']:null,'class_name'=>$row['class_name'],'stream_id'=>$row['stream_id']!==null?(int)$row['stream_id']:null,'stream_name'=>$row['stream_name'],'periods_per_week'=>$periods];}return array_values($matrix);
    }
}
