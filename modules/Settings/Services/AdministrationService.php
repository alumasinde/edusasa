<?php

declare(strict_types=1);

namespace Modules\Settings\Services;

use App\Core\AuditLog;
use App\Core\Auth;
use App\Core\Database;
use App\Core\Tenant;
use RuntimeException;

final class AdministrationService
{
    public function __construct(private readonly Database $db, private readonly Auth $auth) {}

    public function settings(): array
    {
        $school=$this->school();
        $settings=[];
        if (!empty($school['settings_json'])) {
            $decoded=json_decode((string)$school['settings_json'],true);
            if(is_array($decoded)) $settings=$decoded;
        }
        return ['school'=>$school,'settings'=>$settings];
    }

    public function update(array $data): void
    {
        $school=$this->school();
        $settings=[
            'default_currency'=>trim((string)($data['default_currency']??'KES')),
            'date_format'=>trim((string)($data['date_format']??'Y-m-d')),
            'attendance_cutoff_time'=>trim((string)($data['attendance_cutoff_time']??'09:00')),
            'academic_year_label'=>trim((string)($data['academic_year_label']??'')),
            'term_label'=>trim((string)($data['term_label']??'')),
            'notifications_enabled'=>isset($data['notifications_enabled']) && (string)$data['notifications_enabled']==='1',
        ];
        if(!preg_match('/^[A-Z]{3}$/',$settings['default_currency'])) throw new RuntimeException('Currency must be a 3-letter code.');
        if(!preg_match('/^\d{2}:\d{2}$/',$settings['attendance_cutoff_time'])) throw new RuntimeException('Attendance cutoff must use HH:MM.');
        $this->db->execute('UPDATE schools SET name=:name,email=:email,phone=:phone,address=:address,timezone=:timezone,settings_json=:settings WHERE id=:id',['name'=>trim((string)$data['name']),'email'=>trim((string)$data['email'])?:null,'phone'=>trim((string)$data['phone'])?:null,'address'=>trim((string)$data['address'])?:null,'timezone'=>trim((string)$data['timezone'])?:'Africa/Nairobi','settings'=>json_encode($settings,JSON_THROW_ON_ERROR),'id'=>(int)$school['id']]);
        AuditLog::record('settings.school.updated','schools',(int)$school['id'],null,['fields'=>['name','email','phone','address','timezone','settings']]);
    }

    public function audit(int $page=1): array
    {
        $offset=max(0,($page-1)*50);
        $rows=$this->db->select('SELECT id,platform_user_id,action,resource_type,resource_id,metadata_json,ip_address,created_at FROM platform_audit_logs WHERE school_id=:school_id ORDER BY created_at DESC,id DESC LIMIT 50 OFFSET '.$offset,['school_id'=>Tenant::id()]);
        return ['rows'=>$rows,'page'=>$page];
    }

    private function school(): array
    {
        $id=Tenant::id();
        if($id===null) throw new RuntimeException('School context is required.');
        $row=$this->db->selectOne('SELECT id,code,name,slug,email,phone,address,logo_url,domain,status,timezone,settings_json FROM schools WHERE id=:id LIMIT 1',['id'=>$id]);
        if($row===null) throw new RuntimeException('School not found.');
        return $row;
    }
}
