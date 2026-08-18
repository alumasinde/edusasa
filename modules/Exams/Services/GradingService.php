<?php

declare(strict_types=1);

namespace Modules\Exams\Services;

use App\Core\Database;
use App\Core\Tenant;
use RuntimeException;

final class GradingService
{
    public function __construct(private readonly Database $db) {}

    public function pageData(int $examId): array
    {
        $schoolId=Tenant::id();
        $exam=$this->db->selectOne('SELECT e.*, ay.name academic_year_name,t.name term_name FROM examinations e JOIN academic_years ay ON ay.id=e.academic_year_id AND ay.school_id=e.school_id JOIN terms t ON t.id=e.term_id AND t.school_id=e.school_id WHERE e.id=:id AND e.school_id=:school_id',['id'=>$examId,'school_id'=>$schoolId]);
        if(!$exam) throw new RuntimeException('Examination not found.');
        $scale=$this->db->selectOne('SELECT * FROM assessment_grade_scales WHERE school_id=:school_id AND status="active" AND is_default=1 ORDER BY id LIMIT 1',['school_id'=>$schoolId]);
        if(!$scale) $scale=$this->db->selectOne('SELECT * FROM assessment_grade_scales WHERE school_id=:school_id AND status="active" ORDER BY id LIMIT 1',['school_id'=>$schoolId]);
        $items=$scale?$this->db->select('SELECT * FROM assessment_grade_scale_items WHERE scale_id=:scale_id ORDER BY min_percentage DESC',['scale_id'=>(int)$scale['id']]):[];
        $results=$this->db->select('SELECT r.*,st.admission_no,st.first_name,st.middle_name,st.last_name FROM examination_results r JOIN students st ON st.id=r.student_id AND st.school_id=r.school_id WHERE r.school_id=:school_id AND r.examination_id=:exam_id ORDER BY r.percentage DESC,st.admission_no',['school_id'=>$schoolId,'exam_id'=>$examId]);
        return ['exam'=>$exam,'scale'=>$scale,'items'=>$items,'results'=>$results];
    }

    public function createScale(string $name,string $code,array $items,bool $default): int
    {
        $schoolId=Tenant::id(); $name=trim($name);$code=trim($code);
        if($name===''||$code==='') throw new RuntimeException('Grade scale name and code are required.');
        if(count($items)<2) throw new RuntimeException('A grade scale requires at least two grades.');
        $clean=[];
        foreach($items as $item){$grade=trim((string)($item['grade']??''));$min=(float)($item['min_percentage']??-1);$max=(float)($item['max_percentage']??-1);$points=(float)($item['points']??0);if($grade===''||$min<0||$max>100||$min>$max)throw new RuntimeException('Invalid grade boundary.');$clean[]=['grade'=>$grade,'min'=>$min,'max'=>$max,'points'=>$points,'remark'=>trim((string)($item['remark']??''))?:null];}
        usort($clean,fn($a,$b)=>$b['min']<=>$a['min']);
        for($i=0;$i<count($clean)-1;$i++) if($clean[$i]['min'] < $clean[$i+1]['max']) throw new RuntimeException('Grade boundaries overlap.');
        return (int)$this->db->transaction(function()use($schoolId,$name,$code,$clean,$default){
            if($default)$this->db->execute('UPDATE assessment_grade_scales SET is_default=0 WHERE school_id=:school_id',['school_id'=>$schoolId]);
            $this->db->execute('INSERT INTO assessment_grade_scales(school_id,name,code,is_default) VALUES(:school_id,:name,:code,:is_default)',['school_id'=>$schoolId,'name'=>$name,'code'=>$code,'is_default'=>$default?1:0]);$id=(int)$this->db->lastInsertId();
            foreach($clean as $i=>$g)$this->db->execute('INSERT INTO assessment_grade_scale_items(scale_id,grade,min_percentage,max_percentage,points,remark,sort_order) VALUES(:scale_id,:grade,:min,:max,:points,:remark,:sort_order)',['scale_id'=>$id,'grade'=>$g['grade'],'min'=>$g['min'],'max'=>$g['max'],'points'=>$g['points'],'remark'=>$g['remark'],'sort_order'=>$i]);
            return $id;
        });
    }

    public function calculate(int $examId,int $scaleId): int
    {
        $schoolId=Tenant::id();
        $exam=$this->db->selectOne('SELECT id,status FROM examinations WHERE id=:id AND school_id=:school_id',['id'=>$examId,'school_id'=>$schoolId]);
        if(!$exam)throw new RuntimeException('Examination not found.');
        if(!in_array((string)$exam['status'],['closed','published'],true))throw new RuntimeException('Close the examination before calculating final results.');
        $scale=$this->db->selectOne('SELECT * FROM assessment_grade_scales WHERE id=:id AND school_id=:school_id AND status="active"',['id'=>$scaleId,'school_id'=>$schoolId]);if(!$scale)throw new RuntimeException('Grade scale not found.');
        $items=$this->db->select('SELECT * FROM assessment_grade_scale_items WHERE scale_id=:scale_id ORDER BY min_percentage DESC',['scale_id'=>$scaleId]);if(!$items)throw new RuntimeException('Grade scale has no boundaries.');
        $students=$this->db->select('SELECT DISTINCT st.id FROM students st JOIN examination_papers p ON p.class_id=st.current_class_id AND p.school_id=st.school_id JOIN examination_marks m ON m.student_id=st.id AND m.paper_id=p.id AND m.examination_id=:exam_id AND m.school_id=st.school_id WHERE st.school_id=:school_id AND st.status="active" AND st.deleted_at IS NULL AND p.examination_id=:exam_id',['school_id'=>$schoolId,'exam_id'=>$examId]);
        if(!$students)throw new RuntimeException('No recorded marks were found for this examination.');
        $papers=$this->db->select('SELECT p.id,p.max_marks,p.weight FROM examination_papers p WHERE p.examination_id=:exam_id AND p.school_id=:school_id AND p.status="locked"',['exam_id'=>$examId,'school_id'=>$schoolId]);if(!$papers)throw new RuntimeException('No locked examination papers are available.');
        $count=0;$this->db->transaction(function()use(&$count,$students,$papers,$items,$schoolId,$examId,$scaleId){foreach($students as $st){$total=0;$maximum=0;$absent=0;$weightTotal=0;$weighted=0;foreach($papers as $p){$m=$this->db->selectOne('SELECT marks,is_absent FROM examination_marks WHERE school_id=:school_id AND examination_id=:exam_id AND paper_id=:paper_id AND student_id=:student_id',['school_id'=>$schoolId,'exam_id'=>$examId,'paper_id'=>$p['id'],'student_id'=>$st['id']]);$weight=(float)$p['weight'];$max=(float)$p['max_marks'];$weightTotal+=$weight;$maximum+=$max;if(!$m){$absent++;continue;}if((int)$m['is_absent']){$absent++;continue;}$mark=(float)$m['marks'];$total+=$mark;$weighted+=($max>0?($mark/$max)*$weight:0);}if($weightTotal<=0)throw new RuntimeException('Paper weights must total more than zero.');$percentage=round(($weighted/$weightTotal)*100,2);$grade=null;$points=0;$remark=null;foreach($items as $g){if($percentage >= (float)$g['min_percentage'] && $percentage <= (float)$g['max_percentage']){$grade=$g['grade'];$points=(float)$g['points'];$remark=$g['remark'];break;}}$this->db->execute('INSERT INTO examination_results(school_id,examination_id,student_id,grade_scale_id,total_marks,maximum_marks,percentage,grade,points,remark,absent_papers,status,calculated_at) VALUES(:school_id,:exam_id,:student_id,:scale_id,:total,:maximum,:percentage,:grade,:points,:remark,:absent,"draft",NOW()) ON DUPLICATE KEY UPDATE grade_scale_id=VALUES(grade_scale_id),total_marks=VALUES(total_marks),maximum_marks=VALUES(maximum_marks),percentage=VALUES(percentage),grade=VALUES(grade),points=VALUES(points),remark=VALUES(remark),absent_papers=VALUES(absent_papers),calculated_at=NOW(),status="draft"',['school_id'=>$schoolId,'exam_id'=>$examId,'student_id'=>$st['id'],'scale_id'=>$scaleId,'total'=>$total,'maximum'=>$maximum,'percentage'=>$percentage,'grade'=>$grade,'points'=>$points,'remark'=>$remark,'absent'=>$absent]);$count++;}});return $count;
    }
}
