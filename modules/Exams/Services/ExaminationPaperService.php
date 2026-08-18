<?php

declare(strict_types=1);

namespace Modules\Exams\Services;

use App\Core\Database;
use App\Core\Tenant;
use RuntimeException;

final class ExaminationPaperService
{
    public function __construct(private readonly Database $db) {}

    public function findExam(int $examId): ?array
    {
        return $this->db->selectOne('SELECT e.*, ay.name academic_year_name, t.name term_name FROM examinations e JOIN academic_years ay ON ay.id=e.academic_year_id JOIN terms t ON t.id=e.term_id WHERE e.id=:id AND e.school_id=:school_id', ['id'=>$examId,'school_id'=>Tenant::id()]);
    }

    public function pageData(int $examId): array
    {
        $schoolId=Tenant::id();
        $exam=$this->findExam($examId);
        if(!$exam) throw new RuntimeException('Examination not found.');
        $classes=$this->db->select('SELECT c.id,c.name,c.code FROM examination_classes ec JOIN classes c ON c.id=ec.class_id WHERE ec.examination_id=:exam_id AND ec.school_id=:school_id ORDER BY c.name', ['exam_id'=>$examId,'school_id'=>$schoolId]);
        $subjects=$this->db->select('SELECT cs.class_id,s.id subject_id,s.name subject_name,s.code subject_code FROM class_subjects cs JOIN subjects s ON s.id=cs.subject_id AND s.school_id=cs.school_id WHERE cs.school_id=:school_id AND s.status="active" AND cs.class_id IN (SELECT class_id FROM examination_classes WHERE examination_id=:exam_id AND school_id=:school_id) ORDER BY cs.class_id,s.name', ['school_id'=>$schoolId,'exam_id'=>$examId]);
        $papers=$this->db->select('SELECT p.*,c.name class_name,c.code class_code,s.name subject_name,s.code subject_code FROM examination_papers p JOIN classes c ON c.id=p.class_id JOIN subjects s ON s.id=p.subject_id WHERE p.examination_id=:exam_id AND p.school_id=:school_id ORDER BY c.name,s.name,p.paper_code', ['exam_id'=>$examId,'school_id'=>$schoolId]);
        return ['exam'=>$exam,'classes'=>$classes,'subjects'=>$subjects,'papers'=>$papers];
    }

    public function create(int $examId, array $data, int $userId): int
    {
        $schoolId=Tenant::id();
        $classId=(int)($data['class_id']??0); $subjectId=(int)($data['subject_id']??0);
        $paperCode=strtoupper(trim((string)($data['paper_code']??''))); $paperName=trim((string)($data['paper_name']??''));
        $max=(float)($data['max_marks']??0); $pass=(float)($data['pass_marks']??0); $weight=(float)($data['weight']??100);
        $duration=$data['duration_minutes']!=='' && $data['duration_minutes']!==null ? (int)$data['duration_minutes'] : null;
        $scheduled=trim((string)($data['scheduled_at']??'')) ?: null;
        if(!$this->findExam($examId)) throw new RuntimeException('Examination not found.');
        if($classId<1||$subjectId<1||$paperCode===''||$paperName===''||$max<=0) throw new RuntimeException('Complete all required paper fields.');
        if($pass<0||$pass>$max) throw new RuntimeException('Pass marks must be between zero and maximum marks.');
        if($weight<=0||$weight>100) throw new RuntimeException('Weight must be greater than zero and not exceed 100%.');
        if($duration!==null&&($duration<1||$duration>1440)) throw new RuntimeException('Duration must be between 1 and 1440 minutes.');
        $valid=$this->db->selectOne('SELECT ec.class_id,cs.subject_id FROM examination_classes ec JOIN class_subjects cs ON cs.class_id=ec.class_id AND cs.school_id=ec.school_id WHERE ec.examination_id=:exam_id AND ec.class_id=:class_id AND ec.school_id=:school_id AND cs.subject_id=:subject_id', ['exam_id'=>$examId,'class_id'=>$classId,'school_id'=>$schoolId,'subject_id'=>$subjectId]);
        if(!$valid) throw new RuntimeException('The selected subject is not assigned to the selected examination class.');
        try {
            return (int)$this->db->insert('INSERT INTO examination_papers(school_id,examination_id,class_id,subject_id,paper_code,paper_name,max_marks,pass_marks,weight,duration_minutes,scheduled_at,status,instructions,created_by) VALUES(:school_id,:exam_id,:class_id,:subject_id,:code,:name,:max,:pass,:weight,:duration,:scheduled,"draft",:instructions,:user_id)', ['school_id'=>$schoolId,'exam_id'=>$examId,'class_id'=>$classId,'subject_id'=>$subjectId,'code'=>$paperCode,'name'=>$paperName,'max'=>$max,'pass'=>$pass,'weight'=>$weight,'duration'=>$duration,'scheduled'=>$scheduled,'instructions'=>trim((string)($data['instructions']??''))?:null,'user_id'=>$userId]);
        } catch (\PDOException $e) {
            if((int)$e->errorInfo[1]===1062) throw new RuntimeException('A paper with this code already exists for this examination, class and subject.');
            throw $e;
        }
    }

    public function updateStatus(int $paperId,string $status): void
    {
        if(!in_array($status,['draft','ready','locked'],true)) throw new RuntimeException('Invalid paper status.');
        $paper=$this->db->selectOne('SELECT id,status FROM examination_papers WHERE id=:id AND school_id=:school_id',['id'=>$paperId,'school_id'=>Tenant::id()]);
        if(!$paper) throw new RuntimeException('Paper not found.');
        $transitions=['draft'=>['ready','locked'],'ready'=>['draft','locked'],'locked'=>[]];
        if(!in_array($status,$transitions[$paper['status']]??[],true)) throw new RuntimeException('Invalid paper status transition.');
        $this->db->execute('UPDATE examination_papers SET status=:status WHERE id=:id AND school_id=:school_id',['status'=>$status,'id'=>$paperId,'school_id'=>Tenant::id()]);
    }

    public function delete(int $paperId): void
    {
        $paper=$this->db->selectOne('SELECT id,status FROM examination_papers WHERE id=:id AND school_id=:school_id',['id'=>$paperId,'school_id'=>Tenant::id()]);
        if(!$paper) throw new RuntimeException('Paper not found.');
        if($paper['status']==='locked') throw new RuntimeException('Locked papers cannot be deleted.');
        $this->db->delete('DELETE FROM examination_papers WHERE id=:id AND school_id=:school_id',['id'=>$paperId,'school_id'=>Tenant::id()]);
    }
}
