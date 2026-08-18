<?php

declare(strict_types=1);

namespace Modules\StudentPortal\Services;

use App\Core\AuditLog;
use App\Core\Auth;
use App\Core\Database;
use App\Core\Tenant;
use RuntimeException;

final class StudentPortalService
{
    public function __construct(private readonly Database $db, private readonly Auth $auth) {}

    public function context(): array
    {
        $userId=$this->auth->id();
        if($userId===null) throw new RuntimeException('Authentication required.');
        $schoolId=Tenant::id();
        $student=$this->db->selectOne('SELECT s.*,c.name class_name,st.name stream_name FROM students s LEFT JOIN classes c ON c.id=s.current_class_id AND c.school_id=s.school_id LEFT JOIN streams st ON st.id=s.current_stream_id AND st.school_id=s.school_id WHERE s.school_id=:school_id AND s.user_id=:user_id AND s.deleted_at IS NULL LIMIT 1',['school_id'=>$schoolId,'user_id'=>$userId]);
        if($student===null) throw new RuntimeException('This account is not linked to a student profile.');
        return ['student'=>$student];
    }

    public function dashboard(): array
    {
        $student=$this->context()['student'];
        $id=(int)$student['id'];$schoolId=Tenant::id();
        $attendance=$this->attendanceSummary($id);
        $fees=$this->db->selectOne('SELECT COALESCE(SUM(balance),0) outstanding,COALESCE(SUM(total),0) invoiced,COALESCE(SUM(paid_amount),0) paid FROM fee_invoices WHERE school_id=:school_id AND student_id=:student_id AND deleted_at IS NULL AND status<>"draft"',['school_id'=>$schoolId,'student_id'=>$id])??[];
        $latest=$this->db->select('SELECT r.percentage,r.grade,r.points,r.remark,e.name examination_name,e.term_id,t.name term_name FROM examination_results r INNER JOIN examinations e ON e.id=r.examination_id AND e.school_id=r.school_id INNER JOIN terms t ON t.id=e.term_id AND t.school_id=e.school_id WHERE r.school_id=:school_id AND r.student_id=:student_id AND r.status="published" AND e.status="published" ORDER BY r.calculated_at DESC,r.id DESC LIMIT 5',['school_id'=>$schoolId,'student_id'=>$id]);
        $notifications=$this->db->select('SELECT c.id,c.title,c.type,c.body,c.published_at,r.read_at FROM communication_recipients r INNER JOIN communications c ON c.id=r.communication_id WHERE r.user_id=:user_id AND c.school_id=:school_id AND c.status="published" ORDER BY c.published_at DESC,c.id DESC LIMIT 5',['user_id'=>(int)$this->auth->id(),'school_id'=>$schoolId]);
        return ['student'=>$student,'attendance'=>$attendance,'fees'=>$fees,'latest_results'=>$latest,'notifications'=>$notifications];
    }

    public function academics(): array
    {
        $student=$this->context()['student'];$id=(int)$student['id'];$schoolId=Tenant::id();
        $cards=$this->db->select('SELECT c.id,c.status,c.generated_at,c.published_at,e.name examination_name,e.term_id,t.name term_name FROM examination_report_cards c INNER JOIN examinations e ON e.id=c.examination_id AND e.school_id=c.school_id INNER JOIN terms t ON t.id=e.term_id AND t.school_id=e.school_id WHERE c.school_id=:school_id AND c.student_id=:student_id AND c.status="published" AND e.status="published" ORDER BY c.published_at DESC,c.id DESC LIMIT 20',['school_id'=>$schoolId,'student_id'=>$id]);
        $results=$this->db->select('SELECT r.id,r.percentage,r.grade,r.points,r.remark,s.name subject_name,e.name examination_name,t.name term_name FROM examination_results r INNER JOIN subjects s ON s.id=r.subject_id LEFT JOIN examinations e ON e.id=r.examination_id AND e.school_id=r.school_id LEFT JOIN terms t ON t.id=e.term_id AND t.school_id=e.school_id WHERE r.school_id=:school_id AND r.student_id=:student_id AND r.status="published" AND e.status="published" ORDER BY e.id DESC,s.name ASC',['school_id'=>$schoolId,'student_id'=>$id]);
        return ['student'=>$student,'report_cards'=>$cards,'results'=>$results];
    }

    public function timetable(): array
    {
        $student=$this->context()['student'];$schoolId=Tenant::id();
        $rows=$this->db->select('SELECT tt.id timetable_id,tt.name timetable_name,te.day_of_week,te.entry_type,p.period_no,p.name period_name,p.starts_at,p.ends_at,sub.name subject_name,CONCAT(t.first_name," ",t.last_name) teacher_name FROM timetable_entries te INNER JOIN timetables tt ON tt.id=te.timetable_id AND tt.school_id=:school_id AND tt.status="published" INNER JOIN timetable_periods p ON p.id=te.period_id AND p.school_id=te.school_id LEFT JOIN subjects sub ON sub.id=te.subject_id LEFT JOIN teachers t ON t.id=te.teacher_id WHERE te.school_id=:school_id AND te.class_id=:class_id AND (te.stream_id IS NULL OR te.stream_id=:stream_id) ORDER BY te.day_of_week,p.period_no',['school_id'=>$schoolId,'class_id'=>(int)$student['current_class_id'],'stream_id'=>(int)($student['current_stream_id']??0)]);
        return ['student'=>$student,'entries'=>$rows];
    }

    public function attendance(): array
    {
        $student=$this->context()['student'];$id=(int)$student['id'];
        return ['student'=>$student,'summary'=>$this->attendanceSummary($id),'history'=>$this->db->select('SELECT attendance_date,status,remarks FROM attendance WHERE school_id=:school_id AND student_id=:student_id AND deleted_at IS NULL ORDER BY attendance_date DESC LIMIT 100',['school_id'=>Tenant::id(),'student_id'=>$id])];
    }

    public function finance(): array
    {
        $student=$this->context()['student'];$id=(int)$student['id'];$schoolId=Tenant::id();
        $invoices=$this->db->select('SELECT id,invoice_no,invoice_date,due_date,status,total,paid_amount,balance FROM fee_invoices WHERE school_id=:school_id AND student_id=:student_id AND deleted_at IS NULL AND status<>"draft" ORDER BY invoice_date DESC,id DESC LIMIT 50',['school_id'=>$schoolId,'student_id'=>$id]);
        $payments=$this->db->select('SELECT receipt_no,payment_date,amount,method,reference,status FROM fee_payments WHERE school_id=:school_id AND student_id=:student_id ORDER BY payment_date DESC,id DESC LIMIT 50',['school_id'=>$schoolId,'student_id'=>$id]);
        return ['student'=>$student,'invoices'=>$invoices,'payments'=>$payments];
    }

    public function notifications(int $page=1): array
    {
        $student=$this->context()['student'];$userId=(int)$this->auth->id();$offset=max(0,($page-1)*25);$schoolId=Tenant::id();
        $messages=$this->db->select('SELECT c.id,c.title,c.body,c.type,c.published_at,r.read_at FROM communication_recipients r INNER JOIN communications c ON c.id=r.communication_id WHERE r.user_id=:user_id AND c.school_id=:school_id AND c.status="published" ORDER BY c.published_at DESC,c.id DESC LIMIT 25 OFFSET '.$offset,['user_id'=>$userId,'school_id'=>$schoolId]);
        $unread=$this->db->selectOne('SELECT COUNT(*) total FROM communication_recipients r INNER JOIN communications c ON c.id=r.communication_id WHERE r.user_id=:user_id AND c.school_id=:school_id AND c.status="published" AND r.read_at IS NULL',['user_id'=>$userId,'school_id'=>$schoolId]);
        return ['student'=>$student,'messages'=>$messages,'unread'=>(int)($unread['total']??0),'page'=>$page];
    }

    public function markNotificationRead(int $communicationId): void
    {
        $userId=$this->auth->id();if($userId===null)return;
        $this->db->execute('UPDATE communication_recipients r INNER JOIN communications c ON c.id=r.communication_id SET r.read_at=COALESCE(r.read_at,CURRENT_TIMESTAMP) WHERE r.communication_id=:communication_id AND r.user_id=:user_id AND c.school_id=:school_id',['communication_id'=>$communicationId,'user_id'=>$userId,'school_id'=>Tenant::id()]);
    }

    public function updateProfile(array $data): void
    {
        $student=$this->context()['student'];$phone=trim((string)($data['phone']??''));$email=trim((string)($data['email']??''));$address=trim((string)($data['address']??''));
        if($phone!==''&&!preg_match('/^[0-9+() .-]{7,40}$/',$phone))throw new RuntimeException('Enter a valid phone number.');
        if(mb_strlen($address)>500)throw new RuntimeException('Address is too long.');
        $this->db->execute('UPDATE students SET phone=:phone,email=:email,address=:address,updated_at=CURRENT_TIMESTAMP WHERE id=:id AND school_id=:school_id AND user_id=:user_id',['phone'=>$phone!==''?$phone:null,'email'=>$email!==''?$email:null,'address'=>$address!==''?$address:null,'id'=>(int)$student['id'],'school_id'=>Tenant::id(),'user_id'=>(int)$this->auth->id()]);
        AuditLog::record('studentportal.profile.updated','students',(int)$student['id'],null,['fields'=>['phone','email','address']]);
    }

    private function attendanceSummary(int $studentId): array
    {
        $row=$this->db->selectOne("SELECT SUM(status='present') present,SUM(status='absent') absent,SUM(status='late') late,SUM(status='excused') excused,COUNT(*) total FROM attendance WHERE school_id=:school_id AND student_id=:student_id AND deleted_at IS NULL",['school_id'=>Tenant::id(),'student_id'=>$studentId])??[];
        return array_map('intval',$row);
    }
}
