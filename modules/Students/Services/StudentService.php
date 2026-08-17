<?php

declare(strict_types=1);

namespace Modules\Students\Services;

use App\Core\AuditLog;
use App\Core\BaseService;
use App\Core\Session;
use Modules\Students\Repositories\StudentRepository;
use Modules\Students\Repositories\StudentStatusHistoryRepository;

class StudentService extends BaseService
{
    public function __construct(private readonly StudentRepository $students, private readonly StudentStatusHistoryRepository $statusHistory) {}
    public function create(array $data): int
    {
        $admissionNo = trim((string) ($data['admission_no'] ?? ''));
        if ($admissionNo === '') $admissionNo = $this->students->nextAdmissionNumber();
        $id = (int) $this->db()->transaction(function () use ($data, $admissionNo) {
            $studentId = (int) $this->students->create([
                'admission_no'=>$admissionNo,'upi_number'=>$this->blankToNull($data['upi_number']??null),'first_name'=>$data['first_name'],'middle_name'=>$this->blankToNull($data['middle_name']??null),'last_name'=>$data['last_name'],'gender'=>$this->blankToNull($data['gender']??null),'date_of_birth'=>$this->blankToNull($data['date_of_birth']??null),'birth_certificate_no'=>$this->blankToNull($data['birth_certificate_no']??null),'nationality'=>$this->blankToNull($data['nationality']??null),'religion'=>$this->blankToNull($data['religion']??null),'current_class_id'=>$this->blankToNull($data['current_class_id']??null),'current_stream_id'=>$this->blankToNull($data['current_stream_id']??null),'admission_date'=>$this->blankToNull($data['admission_date']??null)??date('Y-m-d'),'student_type'=>$data['student_type']??'day','status'=>'active']);
            $this->statusHistory->create(['student_id'=>$studentId,'from_status'=>null,'to_status'=>'active','reason'=>'Admitted','changed_by'=>Session::get('user_id')]);
            return $studentId;
        });
        AuditLog::record('student.created','students',$id,null,$data); return $id;
    }
    public function update(int $id,array $data): void
    {
        $before=$this->students->findOrFail($id);
        $this->students->update($id,['admission_no'=>$data['admission_no'],'upi_number'=>$this->blankToNull($data['upi_number']??null),'first_name'=>$data['first_name'],'middle_name'=>$this->blankToNull($data['middle_name']??null),'last_name'=>$data['last_name'],'gender'=>$this->blankToNull($data['gender']??null),'date_of_birth'=>$this->blankToNull($data['date_of_birth']??null),'birth_certificate_no'=>$this->blankToNull($data['birth_certificate_no']??null),'nationality'=>$this->blankToNull($data['nationality']??null),'religion'=>$this->blankToNull($data['religion']??null),'current_class_id'=>$this->blankToNull($data['current_class_id']??null),'current_stream_id'=>$this->blankToNull($data['current_stream_id']??null),'admission_date'=>$this->blankToNull($data['admission_date']??null),'student_type'=>$data['student_type']??'day']);
        AuditLog::record('student.updated','students',$id,$before,$data);
    }
    public function changeStatus(int $id,string $newStatus,string $reason=''): void
    {
        $before=$this->students->findOrFail($id); if($before['status']===$newStatus)return;
        $this->db()->transaction(function()use($id,$before,$newStatus,$reason){$this->students->update($id,['status'=>$newStatus]);$this->statusHistory->create(['student_id'=>$id,'from_status'=>$before['status'],'to_status'=>$newStatus,'reason'=>$reason!==''?$reason:null,'changed_by'=>Session::get('user_id')]);});
        AuditLog::record('student.status_changed','students',$id,['status'=>$before['status']],['status'=>$newStatus]);
    }
    public function delete(int $id): void { $before=$this->students->findOrFail($id);$this->students->delete($id);AuditLog::record('student.deleted','students',$id,$before,null); }
    private function blankToNull(mixed $value): mixed { return ($value===''||$value===null)?null:$value; }
}