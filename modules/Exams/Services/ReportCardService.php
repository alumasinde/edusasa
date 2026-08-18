<?php

declare(strict_types=1);

namespace Modules\Exams\Services;

use App\Core\Database;
use App\Core\Tenant;
use RuntimeException;

final class ReportCardService
{
    public function __construct(private readonly Database $db) {}

    public function pageData(int $examId): array
    {
        $schoolId=Tenant::id();
        $exam=$this->db->selectOne('SELECT e.*,ay.name academic_year_name,t.name term_name FROM examinations e JOIN academic_years ay ON ay.id=e.academic_year_id AND ay.school_id=e.school_id JOIN terms t ON t.id=e.term_id AND t.school_id=e.school_id WHERE e.id=:id AND e.school_id=:school_id',['id'=>$examId,'school_id'=>$schoolId]);
        if(!$exam) throw new RuntimeException('Examination not found.');
        $cards=$this->db->select('SELECT c.*,s.admission_no,s.first_name,s.middle_name,s.last_name FROM examination_report_cards c JOIN students s ON s.id=c.student_id AND s.school_id=c.school_id WHERE c.school_id=:school_id AND c.examination_id=:exam_id ORDER BY s.admission_no',['school_id'=>$schoolId,'exam_id'=>$examId]);
        $templates=$this->db->select('SELECT * FROM report_card_templates WHERE school_id=:school_id AND status="active" ORDER BY is_default DESC,name',['school_id'=>$schoolId]);
        return ['exam'=>$exam,'cards'=>$cards,'templates'=>$templates];
    }

    public function reviewData(int $cardId): array
    {
        $schoolId=Tenant::id();
        $card=$this->db->selectOne('SELECT c.*,e.name examination_name,e.status exam_status,s.admission_no,s.first_name,s.middle_name,s.last_name FROM examination_report_cards c JOIN examinations e ON e.id=c.examination_id JOIN students s ON s.id=c.student_id AND s.school_id=c.school_id WHERE c.id=:id AND c.school_id=:school_id',['id'=>$cardId,'school_id'=>$schoolId]);
        if(!$card) throw new RuntimeException('Report card not found.');
        $subjects=$this->db->select('SELECT r.*,sub.name subject_name FROM examination_report_card_subjects r JOIN subjects sub ON sub.id=r.subject_id WHERE r.report_card_id=:id ORDER BY sub.name',['id'=>$cardId]);
        $reviews=$this->db->select('SELECT r.*,u.first_name,u.last_name FROM examination_report_card_reviews r LEFT JOIN users u ON u.id=r.actor_id WHERE r.report_card_id=:id AND r.school_id=:school_id ORDER BY r.created_at DESC',['id'=>$cardId,'school_id'=>$schoolId]);
        return ['card'=>$card,'subjects'=>$subjects,'reviews'=>$reviews];
    }

    public function updateRemarks(int $cardId,string $teacherRemark,string $principalRemark,int $actorId): void
    {
        $schoolId=Tenant::id();
        $card=$this->db->selectOne('SELECT id,status FROM examination_report_cards WHERE id=:id AND school_id=:school_id',['id'=>$cardId,'school_id'=>$schoolId]);
        if(!$card) throw new RuntimeException('Report card not found.');
        if($card['status']==='published') throw new RuntimeException('Published report cards cannot be edited.');
        $teacher=trim($teacherRemark);$principal=trim($principalRemark);
        if(strlen($teacher)>1000||strlen($principal)>1000) throw new RuntimeException('Remarks cannot exceed 1000 characters.');
        $this->db->transaction(function()use($cardId,$schoolId,$teacher,$principal,$actorId){
            $this->db->execute('UPDATE examination_report_cards SET teacher_remark=:teacher,principal_remark=:principal,updated_at=NOW() WHERE id=:id AND school_id=:school_id',['teacher'=>$teacher!==''?$teacher:null,'principal'=>$principal!==''?$principal:null,'id'=>$cardId,'school_id'=>$schoolId]);
            $this->db->execute('INSERT INTO examination_report_card_reviews(school_id,report_card_id,action,actor_id) VALUES(:school_id,:card_id,"remark_updated",:actor_id)',['school_id'=>$schoolId,'card_id'=>$cardId,'actor_id'=>$actorId]);
        });
    }

    public function approve(int $cardId,int $actorId): void
    {
        $schoolId=Tenant::id();$card=$this->db->selectOne('SELECT c.*,e.status exam_status,r.status result_status FROM examination_report_cards c JOIN examinations e ON e.id=c.examination_id JOIN examination_results r ON r.examination_id=c.examination_id AND r.student_id=c.student_id WHERE c.id=:id AND c.school_id=:school_id',['id'=>$cardId,'school_id'=>$schoolId]);
        if(!$card) throw new RuntimeException('Report card not found.');
        if($card['status']==='published') throw new RuntimeException('Report card is already published.');
        if($card['exam_status']!=='published'||$card['result_status']!=='published') throw new RuntimeException('Only published results can be reviewed.');
        $this->db->execute('UPDATE examination_report_cards SET status="generated",updated_at=NOW() WHERE id=:id AND school_id=:school_id',['id'=>$cardId,'school_id'=>$schoolId]);
        $this->db->execute('INSERT INTO examination_report_card_reviews(school_id,report_card_id,action,actor_id) VALUES(:school_id,:card_id,"approved",:actor_id)',['school_id'=>$schoolId,'card_id'=>$cardId,'actor_id'=>$actorId]);
    }

    public function publish(int $cardId): void
    {
        $schoolId=Tenant::id();$card=$this->db->selectOne('SELECT c.*,e.status exam_status,r.status result_status FROM examination_report_cards c JOIN examinations e ON e.id=c.examination_id JOIN examination_results r ON r.examination_id=c.examination_id AND r.student_id=c.student_id WHERE c.id=:id AND c.school_id=:school_id',['id'=>$cardId,'school_id'=>$schoolId]);
        if(!$card)throw new RuntimeException('Report card not found.');
        if($card['exam_status']!=='published'||$card['result_status']!=='published')throw new RuntimeException('Only published examination results can be published as a report card.');
        $this->db->execute('UPDATE examination_report_cards SET status="published",published_at=NOW() WHERE id=:id AND school_id=:school_id',['id'=>$cardId,'school_id'=>$schoolId]);
        $this->db->execute('INSERT INTO examination_report_card_reviews(school_id,report_card_id,action,actor_id) VALUES(:school_id,:card_id,"published",NULL)',['school_id'=>$schoolId,'card_id'=>$cardId]);
    }

    public function generate(int $examId, ?int $studentId=null): int
    {
        $schoolId=Tenant::id();$exam=$this->db->selectOne('SELECT id,status FROM examinations WHERE id=:id AND school_id=:school_id',['id'=>$examId,'school_id'=>$schoolId]);
        if(!$exam||$exam['status']!=='published')throw new RuntimeException('Only published examinations can generate report cards.');
        $where=$studentId?' AND r.student_id=:student_id':'';$params=['school_id'=>$schoolId,'exam_id'=>$examId];if($studentId)$params['student_id']=$studentId;
        $results=$this->db->select('SELECT r.*,s.admission_no FROM examination_results r JOIN students s ON s.id=r.student_id AND s.school_id=r.school_id WHERE r.school_id=:school_id AND r.examination_id=:exam_id AND r.status="published"'.$where,$params);
        if(!$results)throw new RuntimeException('No published student results are available.');
        $template=$this->db->selectOne('SELECT * FROM report_card_templates WHERE school_id=:school_id AND status="active" ORDER BY is_default DESC,id LIMIT 1',['school_id'=>$schoolId]);$count=0;
        $this->db->transaction(function()use($results,$examId,$schoolId,$template,&$count){foreach($results as $result){$this->db->execute('INSERT INTO examination_report_cards(school_id,examination_id,student_id,template_id,status,generated_at) VALUES(:school_id,:exam_id,:student_id,:template_id,"generated",NOW()) ON DUPLICATE KEY UPDATE template_id=VALUES(template_id),status="generated",generated_at=NOW(),updated_at=NOW()',['school_id'=>$schoolId,'exam_id'=>$examId,'student_id'=>$result['student_id'],'template_id'=>$template['id']??null]);$cardId=(int)$this->db->selectOne('SELECT id FROM examination_report_cards WHERE school_id=:school_id AND examination_id=:exam_id AND student_id=:student_id',['school_id'=>$schoolId,'exam_id'=>$examId,'student_id'=>$result['student_id']])['id'];$this->db->execute('DELETE FROM examination_report_card_subjects WHERE report_card_id=:id',['id'=>$cardId]);$subjects=$this->db->select('SELECT p.subject_id,SUM(CASE WHEN m.is_absent=1 THEN 0 ELSE m.marks END) marks,SUM(p.max_marks) maximum_marks,MAX(m.is_absent) is_absent,MAX(mr.grade) grade,MAX(mr.points) points,MAX(mr.remark) remark FROM examination_papers p JOIN examination_marks m ON m.paper_id=p.id AND m.examination_id=p.examination_id AND m.student_id=:student_id AND m.school_id=p.school_id LEFT JOIN examination_results mr ON mr.examination_id=p.examination_id AND mr.student_id=m.student_id AND mr.school_id=m.school_id WHERE p.examination_id=:exam_id AND p.school_id=:school_id AND p.status="locked" GROUP BY p.subject_id',['student_id'=>$result['student_id'],'exam_id'=>$examId,'school_id'=>$schoolId]);foreach($subjects as $sub){$max=(float)$sub['maximum_marks'];$marks=(float)$sub['marks'];$this->db->execute('INSERT INTO examination_report_card_subjects(report_card_id,subject_id,marks,maximum_marks,percentage,grade,points,remark,is_absent) VALUES(:card,:subject_id,:marks,:maximum,:percentage,:grade,:points,:remark,:absent)',['card'=>$cardId,'subject_id'=>$sub['subject_id'],'marks'=>$marks,'maximum'=>$max,'percentage'=>$max>0?round($marks/$max*100,2):0,'grade'=>$sub['grade'],'points'=>$sub['points']??0,'remark'=>$sub['remark'],'absent'=>(int)$sub['is_absent']]);}$count++;}});return $count;
    }
}
