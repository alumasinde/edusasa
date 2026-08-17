<?php

declare(strict_types=1);

namespace Modules\Students\Services;

use App\Core\AuditLog;
use App\Core\BaseService;
use App\Core\Database;
use App\Core\Mail;
use App\Core\Tenant;
use App\Core\ValidationException;
use Modules\Auth\Services\AuthService;
use Modules\Students\Repositories\GuardianRepository;
use Modules\Students\Repositories\StudentGuardianRepository;

class GuardianService extends BaseService
{
    public function __construct(private readonly GuardianRepository $guardians, private readonly StudentGuardianRepository $studentGuardians, private readonly AuthService $authService) {}

    public function attachToStudent(int $studentId,array $data): int
    {
        $email=trim((string)($data['email']??'')); $phone=trim((string)($data['phone']??''));
        if($email===''&&$phone==='') throw new ValidationException(['email'=>['Provide at least an email or phone number for the guardian.']]);
        $guardianId=(int)$this->db()->transaction(function()use($studentId,$data,$email,$phone){
            $id=$this->findOrCreateGuardian($email,$phone,$data);
            if(!empty($data['create_login'])) $this->ensurePortalAccount($id,$email);
            $this->linkGuardianToStudent($studentId,$id,$data); return $id;
        });
        AuditLog::record('guardian.attached','student_guardians',$studentId,null,['guardian_id'=>$guardianId,'relationship'=>$data['relationship']??null]);
        if(!empty($data['create_login'])&&!empty($data['send_setup_email'])&&$email!=='') $this->sendSetupEmailIfLinked($guardianId,$email);
        return $guardianId;
    }

    public function updateProfile(int $guardianId,array $data): void
    {
        $before=$this->guardians->findOrFail($guardianId);
        $this->guardians->update($guardianId,['first_name'=>$data['first_name'],'middle_name'=>($data['middle_name']??'')!==''?$data['middle_name']:null,'last_name'=>$data['last_name'],'occupation'=>($data['occupation']??'')!==''?$data['occupation']:null,'address'=>($data['address']??'')!==''?$data['address']:null,'national_id'=>($data['national_id']??'')!==''?$data['national_id']:null]);
        AuditLog::record('guardian.updated','guardians',$guardianId,$before,$data);
    }

    public function detach(int $studentGuardianId): void
    {
        $before=$this->studentGuardians->findOrFail($studentGuardianId); $this->studentGuardians->delete($studentGuardianId);
        AuditLog::record('guardian.detached','student_guardians',$studentGuardianId,$before,null);
    }

    private function findOrCreateGuardian(string $email,string $phone,array $data): int
    {
        $existing=$this->guardians->findByEmailOrPhone($email,$phone); if($existing!==null)return(int)$existing['id'];
        return(int)$this->guardians->create(['first_name'=>$data['first_name'],'middle_name'=>($data['middle_name']??'')!==''?$data['middle_name']:null,'last_name'=>$data['last_name'],'email'=>$email!==''?$email:null,'phone'=>$phone!==''?$phone:null,'occupation'=>($data['occupation']??'')!==''?$data['occupation']:null,'address'=>($data['address']??'')!==''?$data['address']:null,'national_id'=>($data['national_id']??'')!==''?$data['national_id']:null,'status'=>'active']);
    }

    private function ensurePortalAccount(int $guardianId,string $email): void
    {
        $guardian=$this->guardians->findOrFail($guardianId); if($guardian['user_id']!==null)return;
        if($email==='')throw new ValidationException(['email'=>['An email address is required to create a portal account.']]);
        $db=Database::getInstance(); $schoolId=Tenant::id();
        $existing=$db->selectOne('SELECT id FROM users WHERE school_id=:school_id AND email=:email AND deleted_at IS NULL',['school_id'=>$schoolId,'email'=>$email]);
        $userId=$existing!==null?(int)$existing['id']:(int)$db->insert('INSERT INTO users (school_id,email,status) VALUES (:school_id,:email,:status)',['school_id'=>$schoolId,'email'=>$email,'status'=>'active']);
        $role=$db->selectOne("SELECT id FROM roles WHERE name='parent'");
        if($role!==null)$db->execute('INSERT IGNORE INTO user_roles (user_id,role_id) VALUES (:user_id,:role_id)',['user_id'=>$userId,'role_id'=>$role['id']]);
        $this->guardians->update($guardianId,['user_id'=>$userId]);
    }

    private function linkGuardianToStudent(int $studentId,int $guardianId,array $data): void
    {
        if($this->studentGuardians->existsForStudent($studentId,$guardianId))return;
        $primary=!empty($data['is_primary']); if($primary)$this->studentGuardians->clearPrimaryFlag($studentId);
        $this->studentGuardians->create(['student_id'=>$studentId,'guardian_id'=>$guardianId,'relationship'=>$data['relationship'],'is_primary'=>$primary?1:0]);
    }

    private function sendSetupEmailIfLinked(int $guardianId,string $email): void
    {
        $guardian=$this->guardians->findOrFail($guardianId); if($guardian['user_id']===null)return;
        $token=$this->authService->issuePasswordResetToken($email); if($token===null)return;
        $tenant=Tenant::current(); $resetUrl=url('/reset-password?token='.$token);
        Mail::send($email,'Set up your '.($tenant?->name??'School').' parent portal account','<p>Click the link below to set your password.</p><p><a href="'.e($resetUrl).'">'.e($resetUrl).'</a></p><p>This link expires in 60 minutes.</p>');
    }
}
