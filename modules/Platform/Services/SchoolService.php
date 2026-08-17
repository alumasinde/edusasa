<?php

declare(strict_types=1);

namespace Modules\Platform\Services;

use App\Core\Database;
use InvalidArgumentException;

final class SchoolService
{
    public function __construct(private readonly Database $db) {}

    public function create(array $input, string $planCode = 'starter'): int
    {
        $name = trim((string) ($input['name'] ?? ''));
        if ($name === '') throw new InvalidArgumentException('School name is required.');

        $slug = $this->slug($input['slug'] ?? $name);
        $code = strtoupper(trim((string) ($input['code'] ?? '')));
        if ($code === '') $code = strtoupper(substr(preg_replace('/[^A-Z0-9]/i', '', $slug), 0, 8)) . random_int(100, 999);

        $plan = $this->db->selectOne('SELECT id FROM plans WHERE code=:code AND is_active=1', ['code' => $planCode]);
        if ($plan === null) throw new InvalidArgumentException('Selected plan is not available.');

        return (int) $this->db->transaction(function (Database $db) use ($input,$name,$slug,$code,$plan): string {
            $schoolId = $db->insert(
                'INSERT INTO schools(code,name,slug,email,phone,domain,status,timezone,settings_json)
                 VALUES(:code,:name,:slug,:email,:phone,:domain,\'pending\',:timezone,:settings)',
                [
                    'code'=>$code,'name'=>$name,'slug'=>$slug,
                    'email'=>($input['email'] ?? null),'phone'=>($input['phone'] ?? null),
                    'domain'=>($input['domain'] ?? null),
                    'timezone'=>($input['timezone'] ?? 'Africa/Nairobi'),
                    'settings'=>json_encode(['onboarding'=>'pending'], JSON_THROW_ON_ERROR),
                ]
            );
            $db->insert(
                'INSERT INTO school_subscriptions(school_id,plan_id,status,starts_at) VALUES(:school_id,:plan_id,\'trial\',NOW())',
                ['school_id'=>$schoolId,'plan_id'=>$plan['id']]
            );
            return $schoolId;
        });
    }

    public function setStatus(int $schoolId, string $status): void
    {
        $allowed=['pending','active','suspended','archived'];
        if (!in_array($status,$allowed,true)) throw new InvalidArgumentException('Invalid school status.');
        $this->db->execute('UPDATE schools SET status=:status WHERE id=:id', ['status'=>$status,'id'=>$schoolId]);
    }

    public function list(array $filters = []): array
    {
        $where=[]; $params=[];
        if (!empty($filters['status'])) { $where[]='s.status=:status'; $params['status']=$filters['status']; }
        if (!empty($filters['search'])) { $where[]='(s.name LIKE :search OR s.code LIKE :search OR s.email LIKE :search)'; $params['search']='%'.trim($filters['search']).'%'; }
        $sql='SELECT s.id,s.code,s.name,s.slug,s.email,s.phone,s.domain,s.status,s.created_at,p.code AS plan_code,p.name AS plan_name,ss.status AS subscription_status
              FROM schools s LEFT JOIN school_subscriptions ss ON ss.id=(SELECT MAX(ss2.id) FROM school_subscriptions ss2 WHERE ss2.school_id=s.id)
              LEFT JOIN plans p ON p.id=ss.plan_id';
        if ($where) $sql.=' WHERE '.implode(' AND ',$where);
        return $this->db->select($sql.' ORDER BY s.name LIMIT 200',$params);
    }

    private function slug(string $value): string
    {
        $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i','-', $value), '-'));
        return $slug !== '' ? $slug : 'school-'.bin2hex(random_bytes(4));
    }
}
