<?php

declare(strict_types=1);

namespace Modules\Exams\Services;

use App\Core\Database;
use App\Core\Tenant;
use RuntimeException;

final class ReportCardRenderService
{
    public function __construct(private readonly Database $db) {}

    public function data(int $cardId): array
    {
        $schoolId=Tenant::id();
        $card=$this->db->selectOne('SELECT c.*,e.name examination_name,e.academic_year_id,e.term_id,ay.name academic_year_name,t.name term_name,s.admission_no,s.first_name,s.middle_name,s.last_name,s.current_class_id,rt.name template_name,rt.header_title,rt.school_motto,rt.footer_text,rt.logo_path FROM examination_report_cards c JOIN examinations e ON e.id=c.examination_id JOIN academic_years ay ON ay.id=e.academic_year_id JOIN terms t ON t.id=e.term_id JOIN students s ON s.id=c.student_id AND s.school_id=c.school_id LEFT JOIN report_card_templates rt ON rt.id=c.template_id AND rt.school_id=c.school_id WHERE c.id=:id AND c.school_id=:school_id AND c.status="published"',['id'=>$cardId,'school_id'=>$schoolId]);
        if(!$card) throw new RuntimeException('Published report card not found.');
        $subjects=$this->db->select('SELECT rs.*,sub.name subject_name FROM examination_report_card_subjects rs JOIN subjects sub ON sub.id=rs.subject_id WHERE rs.report_card_id=:card ORDER BY sub.name',['card'=>$cardId]);
        return ['card'=>$card,'subjects'=>$subjects];
    }

    public function attendance(int $studentId, int $schoolId, int $yearId, int $termId): array
    {
        $row=$this->db->selectOne('SELECT COUNT(*) total_days,SUM(status="present") present_days,SUM(status="absent") absent_days,SUM(status="late") late_days FROM attendance WHERE school_id=:school_id AND student_id=:student_id AND academic_year_id=:year_id AND term_id=:term_id',['school_id'=>$schoolId,'student_id'=>$studentId,'year_id'=>$yearId,'term_id'=>$termId]);
        return $row ?: ['total_days'=>0,'present_days'=>0,'absent_days'=>0,'late_days'=>0];
    }

    public function html(int $cardId): string
    {
        $data=$this->data($cardId);$c=$data['card'];$a=$this->attendance((int)$c['student_id'],(int)$c['school_id'],(int)$c['academic_year_id'],(int)$c['term_id']);
        $e=fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');
        $rows='';foreach($data['subjects'] as $s){$rows.='<tr><td>'.$e($s['subject_name']).'</td><td>'.($s['is_absent']?'ABS':$e($s['marks'])).'</td><td>'.$e($s['maximum_marks']).'</td><td>'.$e($s['percentage']).'%</td><td>'.$e($s['grade']??'-').'</td><td>'.$e($s['points']).'</td><td>'.$e($s['remark']??'-').'</td></tr>';}
        $logo=$c['logo_path']?'<img src="'.$e($c['logo_path']).'" class="logo" alt="School logo">':'';
        return '<!doctype html><html><head><meta charset="utf-8"><title>Report Card - '.$e($c['admission_no']).'</title><style>@page{size:A4;margin:16mm}body{font-family:Arial,sans-serif;color:#222;font-size:12px}.sheet{max-width:900px;margin:auto}.header{text-align:center;border-bottom:2px solid #222;padding-bottom:12px}.logo{max-height:80px;max-width:110px}.title{font-size:22px;font-weight:700}.motto{font-style:italic}.student{display:grid;grid-template-columns:1fr 1fr;gap:6px;margin:16px 0}.student div{padding:6px;background:#f6f6f6}table{width:100%;border-collapse:collapse;margin-top:12px}th,td{border:1px solid #999;padding:7px;text-align:left}th{background:#eee}.attendance{margin-top:16px}.remarks{margin-top:20px;display:grid;grid-template-columns:1fr 1fr;gap:20px}.box{border:1px solid #999;min-height:70px;padding:8px}.footer{margin-top:24px;text-align:center;font-size:10px}</style></head><body><main class="sheet"><header class="header">'.$logo.'<div class="title">'.$e($c['header_title']?:'Student Report Card').'</div><div>'.$e($c['school_motto']??'').'</div><h2>'.$e($c['examination_name']).'</h2><div>'.$e($c['academic_year_name']).' — '.$e($c['term_name']).'</div></header><section class="student"><div><b>Student:</b> '.$e(trim($c['first_name'].' '.$c['middle_name'].' '.$c['last_name'])).'</div><div><b>Admission No:</b> '.$e($c['admission_no']).'</div></section><table><thead><tr><th>Subject</th><th>Marks</th><th>Max</th><th>%</th><th>Grade</th><th>Points</th><th>Remark</th></tr></thead><tbody>'.$rows.'</tbody></table><section class="attendance"><h3>Attendance Summary</h3><table><tr><th>Total Days</th><th>Present</th><th>Absent</th><th>Late</th></tr><tr><td>'.$e($a['total_days']).'</td><td>'.$e($a['present_days']).'</td><td>'.$e($a['absent_days']).'</td><td>'.$e($a['late_days']).'</td></tr></table></section><section class="remarks"><div><h3>Teacher Remark</h3><div class="box">'.$e($c['teacher_remark']??'').'</div></div><div><h3>Principal Remark</h3><div class="box">'.$e($c['principal_remark']??'').'</div></div></section><footer class="footer">'.$e($c['footer_text']??'').'</footer></main></body></html>';
    }
}
