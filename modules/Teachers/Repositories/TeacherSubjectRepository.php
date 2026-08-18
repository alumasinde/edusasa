<?php

declare(strict_types=1);

namespace Modules\Teachers\Repositories;

use App\Core\BaseRepository;
use App\Core\Tenant;

final class TeacherSubjectRepository extends BaseRepository
{
    protected function table(): string { return 'teacher_subjects'; }
    protected function tenantScoped(): bool { return true; }
    public function find(int $id): ?array { return $this->db->selectOne('SELECT ts.*, sub.name AS subject_name, c.name AS class_name, str.name AS stream_name FROM teacher_subjects ts INNER JOIN subjects sub ON sub.id=ts.subject_id LEFT JOIN classes c ON c.id=ts.class_id LEFT JOIN streams str ON str.id=ts.stream_id WHERE ts.id=:id AND ts.school_id=:school_id',['id'=>$id,'school_id'=>Tenant::id()]); }
    public function delete(int $id): int { return $this->db->execute('DELETE FROM teacher_subjects WHERE id=:id AND school_id=:school_id',['id'=>$id,'school_id'=>Tenant::id()]); }
    public function forTeacher(int $teacherId): array { return $this->db->select('SELECT ts.*, sub.name AS subject_name, c.name AS class_name, str.name AS stream_name FROM teacher_subjects ts INNER JOIN subjects sub ON sub.id=ts.subject_id LEFT JOIN classes c ON c.id=ts.class_id LEFT JOIN streams str ON str.id=ts.stream_id WHERE ts.teacher_id=:teacher_id AND ts.school_id=:school_id ORDER BY sub.name,c.name,str.name',['teacher_id'=>$teacherId,'school_id'=>Tenant::id()]); }
    public function exists(int $teacherId,int $subjectId,?int $classId,?int $streamId): bool { $sql='SELECT COUNT(*) total FROM teacher_subjects WHERE school_id=:school_id AND teacher_id=:teacher_id AND subject_id=:subject_id AND ';$p=['school_id'=>Tenant::id(),'teacher_id'=>$teacherId,'subject_id'=>$subjectId];if($classId===null)$sql.='class_id IS NULL';else{$sql.='class_id=:class_id AND ';$p['class_id']=$classId;$sql.=$streamId===null?'stream_id IS NULL':'stream_id=:stream_id';if($streamId!==null)$p['stream_id']=$streamId;}$row=$this->db->selectOne($sql,$p);return(int)($row['total']??0)>0; }
    public function allWithNames(): array { return $this->db->select('SELECT ts.*, t.employee_no, CONCAT(t.first_name," ",t.last_name) staff_name, sub.name subject_name, c.name class_name, str.name stream_name FROM teacher_subjects ts JOIN teachers t ON t.id=ts.teacher_id JOIN subjects sub ON sub.id=ts.subject_id LEFT JOIN classes c ON c.id=ts.class_id LEFT JOIN streams str ON str.id=ts.stream_id WHERE ts.school_id=:school_id ORDER BY t.first_name,t.last_name,sub.name',['school_id'=>Tenant::id()]); }
    public function capabilityMap(): array { $rows=$this->db->select('SELECT teacher_id,subject_id FROM teacher_subjects WHERE school_id=:school_id AND class_id IS NULL AND stream_id IS NULL',['school_id'=>Tenant::id()]);$map=[];foreach($rows as $r)$map[(int)$r['teacher_id']][(int)$r['subject_id']]=true;return$map; }
    public function syncCapabilities(array $checked): void { $this->db->transaction(function()use($checked):void{$this->db->execute('DELETE FROM teacher_subjects WHERE school_id=:school_id AND class_id IS NULL AND stream_id IS NULL',['school_id'=>Tenant::id()]);foreach($checked as $teacherId=>$subjects)foreach(array_keys((array)$subjects) as $subjectId){$teacherId=(int)$teacherId;$subjectId=(int)$subjectId;if($teacherId>0&&$subjectId>0)$this->db->execute('INSERT INTO teacher_subjects (school_id,teacher_id,subject_id,class_id,stream_id,periods_per_week,is_double) VALUES (:school_id,:teacher_id,:subject_id,NULL,NULL,NULL,0)',['school_id'=>Tenant::id(),'teacher_id'=>$teacherId,'subject_id'=>$subjectId]);}}); }
}
