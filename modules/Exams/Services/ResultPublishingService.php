<?php

declare(strict_types=1);

namespace Modules\Exams\Services;

use App\Core\Database;
use App\Core\Tenant;
use RuntimeException;

final class ResultPublishingService
{
    public function __construct(private readonly Database $db) {}

    public function pageData(int $examId): array
    {
        $schoolId=Tenant::id();
        $exam=$this->db->selectOne('SELECT e.*,ay.name academic_year_name,t.name term_name FROM examinations e JOIN academic_years ay ON ay.id=e.academic_year_id AND ay.school_id=e.school_id JOIN terms t ON t.id=e.term_id AND t.school_id=e.school_id WHERE e.id=:id AND e.school_id=:school_id',['id'=>$examId,'school_id'=>$schoolId]);
        if(!$exam) throw new RuntimeException('Examination not found.');
        $summary=$this->db->selectOne("SELECT COUNT(*) total, SUM(status='draft') draft_count, SUM(status='approved') approved_count, SUM(status='published') published_count FROM examination_results WHERE school_id=:school_id AND examination_id=:exam_id",['school_id'=>$schoolId,'exam_id'=>$examId]);
        $results=$this->db->select('SELECT r.*,st.admission_no,st.first_name,st.middle_name,st.last_name FROM examination_results r JOIN students st ON st.id=r.student_id AND st.school_id=r.school_id WHERE r.school_id=:school_id AND r.examination_id=:exam_id ORDER BY r.percentage DESC,st.admission_no',['school_id'=>$schoolId,'exam_id'=>$examId]);
        $audit=$this->db->select('SELECT a.*,u.first_name,auser.last_name FROM examination_result_audit a LEFT JOIN users u ON u.id=a.performed_by WHERE a.school_id=:school_id AND a.examination_id=:exam_id ORDER BY a.performed_at DESC LIMIT 100',['school_id'=>$schoolId,'exam_id'=>$examId]);
        return ['exam'=>$exam,'summary'=>$summary,'results'=>$results,'audit'=>$audit];
    }

    public function approve(int $examId,int $userId): int
    {
        return $this->transition($examId,$userId,'draft','approved','approved');
    }

    public function publish(int $examId,int $userId): int
    {
        $schoolId=Tenant::id();
        $exam=$this->db->selectOne('SELECT status FROM examinations WHERE id=:id AND school_id=:school_id',['id'=>$examId,'school_id'=>$schoolId]);
        if(!$exam || $exam['status']!=='published') throw new RuntimeException('The examination itself must be published before results can be published.');
        $draft=(int)$this->db->selectOne('SELECT COUNT(*) c FROM examination_results WHERE school_id=:school_id AND examination_id=:exam_id AND status<>"approved"',['school_id'=>$schoolId,'exam_id'=>$examId])['c'];
        if($draft>0) throw new RuntimeException('All calculated results must be approved before publishing.');
        return $this->transition($examId,$userId,'approved','published','published');
    }

    public function returnForCorrection(int $examId,int $userId,string $reason): int
    {
        $reason=trim($reason); if($reason==='') throw new RuntimeException('A reason is required when returning results for correction.');
        $schoolId=Tenant::id();
        $count=(int)$this->db->selectOne('SELECT COUNT(*) c FROM examination_results WHERE school_id=:school_id AND examination_id=:exam_id AND status="approved"',['school_id'=>$schoolId,'exam_id'=>$examId])['c'];
        if($count===0) throw new RuntimeException('There are no approved results to return.');
        $this->db->transaction(function()use($schoolId,$examId,$userId,$reason){
            $rows=$this->db->select('SELECT id,student_id,status FROM examination_results WHERE school_id=:school_id AND examination_id=:exam_id AND status="approved"',['school_id'=>$schoolId,'exam_id'=>$examId]);
            $this->db->execute('UPDATE examination_results SET status="draft",updated_at=NOW() WHERE school_id=:school_id AND examination_id=:exam_id AND status="approved"',['school_id'=>$schoolId,'exam_id'=>$examId]);
            foreach($rows as $r)$this->audit($schoolId,$examId,$r['student_id'],'returned','approved','draft',$reason,$userId);
        });
        return $count;
    }

    private function transition(int $examId,int $userId,string $from,string $to,string $action): int
    {
        $schoolId=Tenant::id();
        $this->db->beginTransaction();
        try{
            $rows=$this->db->select('SELECT id,student_id,status FROM examination_results WHERE school_id=:school_id AND examination_id=:exam_id AND status=:status FOR UPDATE',['school_id'=>$schoolId,'exam_id'=>$examId,'status'=>$from]);
            if(!$rows) throw new RuntimeException('No results are available for this workflow action.');
            $this->db->execute('UPDATE examination_results SET status=:to,updated_at=NOW() WHERE school_id=:school_id AND examination_id=:exam_id AND status=:from',['to'=>$to,'school_id'=>$schoolId,'exam_id'=>$examId,'from'=>$from]);
            foreach($rows as $r)$this->audit($schoolId,$examId,$r['student_id'],$action,$from,$to,null,$userId);
            $this->db->commit(); return count($rows);
        }catch(\Throwable $e){$this->db->rollBack();throw $e;}
    }

    private function audit(int $schoolId,int $examId,int $studentId,string $action,string $from,string $to,?string $reason,int $userId): void
    {
        $this->db->execute('INSERT INTO examination_result_audit(school_id,examination_id,student_id,action,from_status,to_status,reason,performed_by) VALUES(:school_id,:exam_id,:student_id,:action,:from_status,:to_status,:reason,:performed_by)',['school_id'=>$schoolId,'exam_id'=>$examId,'student_id'=>$studentId,'action'=>$action,'from_status'=>$from,'to_status'=>$to,'reason'=>$reason,'performed_by'=>$userId]);
    }
}
