<?php

declare(strict_types=1);

namespace Modules\ParentPortal\Services;

use App\Core\AuditLog;
use App\Core\Auth;
use App\Core\Database;
use App\Core\Tenant;
use RuntimeException;

final class ParentPortalService
{
    public function __construct(private readonly Database $db, private readonly Auth $auth) {}

    public function context(): array
    {
        $userId=$this->auth->id();
        if($userId===null) throw new RuntimeException('Authentication required.');
        $schoolId=Tenant::id();
        $guardian=$this->db->selectOne('SELECT g.* FROM guardians g WHERE g.school_id=:school_id AND g.user_id=:user_id AND g.deleted_at IS NULL LIMIT 1',['school_id'=>$schoolId,'user_id'=>$userId]);
        if($guardian===null) throw new RuntimeException('This account is not linked to a parent or guardian profile.');
        $children=$this->db->select('SELECT s.id,s.admission_no,s.first_name,s.middle_name,s.last_name,s.gender,s.date_of_birth,s.status,s.current_class_id,s.current_stream_id,sg.relationship,sg.is_primary,c.name class_name,st.name stream_name FROM student_guardians sg INNER JOIN students s ON s.id=sg.student_id AND s.school_id=:school_id AND s.deleted_at IS NULL LEFT JOIN classes c ON c.id=s.current_class_id AND c.school_id=s.school_id LEFT JOIN streams st ON st.id=s.current_stream_id AND st.school_id=s.school_id WHERE sg.guardian_id=:guardian_id AND sg.deleted_at IS NULL ORDER BY sg.is_primary DESC,s.first_name,s.last_name',['school_id'=>$schoolId,'guardian_id'=>(int)$guardian['id']]);
        return ['guardian'=>$guardian,'children'=>$children];
    }

    public function dashboard(): array
    {
        $context=$this->context();
        $children=[];
        foreach($context['children'] as $child){$children[]=$this->childSummary((int)$child['id'],$child);}
        $userId=(int)$this->auth->id();
        $notifications=$this->db->select('SELECT c.id,c.title,c.type,c.body,c.published_at,r.read_at FROM communication_recipients r INNER JOIN communications c ON c.id=r.communication_id WHERE r.user_id=:user_id AND c.school_id=:school_id AND c.status="published" ORDER BY c.published_at DESC,c.id DESC LIMIT 10',['user_id'=>$userId,'school_id'=>Tenant::id()]);
        return ['guardian'=>$context['guardian'],'children'=>$children,'notifications'=>$notifications];
    }

    public function child(int $studentId): array
    {
        $context=$this->context();
        $allowed=false;$selected=null;
        foreach($context['children'] as $child){if((int)$child['id']===$studentId){$allowed=true;$selected=$child;break;}}
        if(!$allowed||$selected===null) throw new RuntimeException('Student is not linked to this parent account.');
        $schoolId=Tenant::id();
        $attendance=$this->db->selectOne("SELECT SUM(status='present') present,SUM(status='absent') absent,SUM(status='late') late,SUM(status='excused') excused,COUNT(*) total FROM attendance WHERE school_id=:school_id AND student_id=:student_id AND deleted_at IS NULL",['school_id'=>$schoolId,'student_id'=>$studentId])??[];
        $invoices=$this->db->select('SELECT id,invoice_no,invoice_date,due_date,status,total,paid_amount,balance FROM fee_invoices WHERE school_id=:school_id AND student_id=:student_id AND deleted_at IS NULL AND status<>"draft" ORDER BY invoice_date DESC,id DESC LIMIT 20',['school_id'=>$schoolId,'student_id'=>$studentId]);
        $payments=$this->db->select('SELECT receipt_no,payment_date,amount,method,reference,status FROM fee_payments WHERE school_id=:school_id AND student_id=:student_id ORDER BY payment_date DESC,id DESC LIMIT 20',['school_id'=>$schoolId,'student_id'=>$studentId]);
        $cards=$this->db->select('SELECT c.id,c.examination_id,c.status,c.generated_at,c.published_at,e.name examination_name,e.term_id,t.name term_name,r.percentage,r.grade,r.points,r.remark FROM examination_report_cards c INNER JOIN examinations e ON e.id=c.examination_id AND e.school_id=c.school_id INNER JOIN terms t ON t.id=e.term_id AND t.school_id=e.school_id LEFT JOIN examination_results r ON r.examination_id=c.examination_id AND r.student_id=c.student_id AND r.school_id=c.school_id WHERE c.school_id=:school_id AND c.student_id=:student_id AND c.status="published" AND e.status="published" ORDER BY c.published_at DESC,c.id DESC LIMIT 10',['school_id'=>$schoolId,'student_id'=>$studentId]);
        $timetable=$this->db->select('SELECT tt.id timetable_id,tt.name timetable_name,tt.status,te.day_of_week,te.entry_type,p.period_no,p.name period_name,p.starts_at,p.ends_at,sub.name subject_name,CONCAT(t.first_name," ",t.last_name) teacher_name FROM timetable_entries te INNER JOIN timetables tt ON tt.id=te.timetable_id AND tt.school_id=:school_id AND tt.status="published" INNER JOIN timetable_periods p ON p.id=te.period_id AND p.school_id=te.school_id LEFT JOIN subjects sub ON sub.id=te.subject_id LEFT JOIN teachers t ON t.id=te.teacher_id WHERE te.school_id=:school_id AND te.class_id=:class_id AND (te.stream_id IS NULL OR te.stream_id=:stream_id) ORDER BY te.day_of_week,p.period_no',['school_id'=>$schoolId,'class_id'=>(int)$selected['current_class_id'],'stream_id'=>(int)($selected['current_stream_id']??0)]);
        $history=$this->db->select('SELECT attendance_date,status,remarks FROM attendance WHERE school_id=:school_id AND student_id=:student_id AND deleted_at IS NULL ORDER BY attendance_date DESC LIMIT 20',['school_id'=>$schoolId,'student_id'=>$studentId]);
        return ['guardian'=>$context['guardian'],'student'=>$selected,'attendance'=>$attendance,'invoices'=>$invoices,'payments'=>$payments,'report_cards'=>$cards,'timetable'=>$timetable,'attendance_history'=>$history];
    }

    public function notifications(int $page=1): array
    {
        $context=$this->context();$userId=(int)$this->auth->id();$offset=max(0,($page-1)*25);
        $messages=$this->db->select('SELECT c.id,c.title,c.body,c.type,c.published_at,r.read_at FROM communication_recipients r INNER JOIN communications c ON c.id=r.communication_id WHERE r.user_id=:user_id AND c.school_id=:school_id AND c.status="published" ORDER BY c.published_at DESC,c.id DESC LIMIT 25 OFFSET '.$offset,['user_id'=>$userId,'school_id'=>Tenant::id()]);
        $unread=$this->db->selectOne('SELECT COUNT(*) total FROM communication_recipients r INNER JOIN communications c ON c.id=r.communication_id WHERE r.user_id=:user_id AND c.school_id=:school_id AND c.status="published" AND r.read_at IS NULL',['user_id'=>$userId,'school_id'=>Tenant::id()]);
        return ['guardian'=>$context['guardian'],'messages'=>$messages,'unread'=>(int)($unread['total']??0),'page'=>$page];
    }

    public function markNotificationRead(int $communicationId): void
    {
        $userId=$this->auth->id();if($userId===null)return;
        $this->db->execute('UPDATE communication_recipients r INNER JOIN communications c ON c.id=r.communication_id SET r.read_at=COALESCE(r.read_at,CURRENT_TIMESTAMP) WHERE r.communication_id=:communication_id AND r.user_id=:user_id AND c.school_id=:school_id',['communication_id'=>$communicationId,'user_id'=>$userId,'school_id'=>Tenant::id()]);
    }

    public function updateProfile(array $data): void
    {
        $context=$this->context();$guardian=$context['guardian'];$phone=trim((string)($data['phone']??''));$address=trim((string)($data['address']??''));
        if($phone!==''&&!preg_match('/^[0-9+() .-]{7,40}$/',$phone))throw new RuntimeException('Enter a valid phone number.');
        if(mb_strlen($address)>500)throw new RuntimeException('Address is too long.');
        $this->db->execute('UPDATE guardians SET phone=:phone,address=:address,updated_at=CURRENT_TIMESTAMP WHERE id=:id AND school_id=:school_id AND user_id=:user_id',['phone'=>$phone!==''?$phone:null,'address'=>$address!==''?$address:null,'id'=>(int)$guardian['id'],'school_id'=>Tenant::id(),'user_id'=>(int)$this->auth->id()]);
        AuditLog::record('parentportal.profile.updated','guardians',(int)$guardian['id'],null,['fields'=>['phone','address']]);
    }

    private function childSummary(int $studentId,array $child): array
    {
        $row=$this->db->selectOne("SELECT SUM(status='present') present,SUM(status='absent') absent,SUM(status='late') late,SUM(status='excused') excused,COUNT(*) total FROM attendance WHERE school_id=:school_id AND student_id=:student_id AND deleted_at IS NULL",['school_id'=>Tenant::id(),'student_id'=>$studentId])??[];
        $fees=$this->db->selectOne('SELECT COALESCE(SUM(balance),0) outstanding FROM fee_invoices WHERE school_id=:school_id AND student_id=:student_id AND deleted_at IS NULL AND status<>"draft"',['school_id'=>Tenant::id(),'student_id'=>$studentId])??[];
        $result=$this->db->selectOne('SELECT r.percentage,r.grade,e.name examination_name FROM examination_results r INNER JOIN examinations e ON e.id=r.examination_id WHERE r.school_id=:school_id AND r.student_id=:student_id AND r.status="published" AND e.status="published" ORDER BY r.calculated_at DESC,r.id DESC LIMIT 1',['school_id'=>Tenant::id(),'student_id'=>$studentId]);
        return $child+['attendance'=>array_map('intval',$row),'outstanding'=>(float)($fees['outstanding']??0),'latest_result'=>$result];
    }
}
