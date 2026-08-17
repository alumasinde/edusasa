<?php

declare(strict_types=1);

namespace Modules\Platform\Services;

use App\Core\Database;
use App\Core\Session;
use InvalidArgumentException;

final class EntitlementAdminService
{
    public function __construct(private readonly Database $db) {}

    public function catalog(): array
    {
        return [
            'plans' => $this->db->select('SELECT p.id,p.code,p.name,p.description,p.price,p.billing_interval,p.is_active,p.sort_order,COUNT(pf.feature_id) feature_count FROM plans p LEFT JOIN plan_features pf ON pf.plan_id=p.id GROUP BY p.id ORDER BY p.sort_order,p.name'),
            'features' => $this->db->select('SELECT id,code,name,module_code,description,is_active FROM features ORDER BY module_code,name'),
        ];
    }

    public function plan(int $planId): ?array
    {
        $plan = $this->db->selectOne('SELECT * FROM plans WHERE id=:id', ['id'=>$planId]);
        if ($plan === null) return null;
        $plan['features'] = $this->db->select('SELECT f.id,f.code,f.name,f.module_code,f.description,f.is_active,pf.limits_json FROM features f LEFT JOIN plan_features pf ON pf.feature_id=f.id AND pf.plan_id=:plan ORDER BY f.module_code,f.name', ['plan'=>$planId]);
        return $plan;
    }

    public function savePlan(array $data, ?int $id = null): int
    {
        $code=trim((string)($data['code']??'')); $name=trim((string)($data['name']??''));
        $interval=trim((string)($data['billing_interval']??'monthly'));
        if($code===''||$name==='') throw new InvalidArgumentException('Plan code and name are required.');
        if(!in_array($interval,['monthly','quarterly','annual','one_time'],true)) throw new InvalidArgumentException('Invalid billing interval.');
        $values=['code'=>$code,'name'=>$name,'description'=>$data['description']??null,'price'=>(float)($data['price']??0),'interval'=>$interval,'active'=>!empty($data['is_active'])?1:0,'sort'=>(int)($data['sort_order']??0)];
        if($id===null){$id=(int)$this->db->insert('INSERT INTO plans(code,name,description,price,billing_interval,is_active,sort_order) VALUES(:code,:name,:description,:price,:interval,:active,:sort)',$values);$this->audit('plan.created','plan',$id,['code'=>$code]);return $id;}
        $this->db->execute('UPDATE plans SET code=:code,name=:name,description=:description,price=:price,billing_interval=:interval,is_active=:active,sort_order=:sort WHERE id=:id',$values+['id'=>$id]);
        $this->audit('plan.updated','plan',$id,['code'=>$code]); return $id;
    }

    public function setPlanFeature(int $planId,int $featureId,bool $enabled,?array $limits):void
    {
        if($planId<1||$featureId<1) throw new InvalidArgumentException('Invalid plan or feature.');
        if($enabled){$this->db->query('INSERT INTO plan_features(plan_id,feature_id,limits_json) VALUES(:plan,:feature,:limits) ON DUPLICATE KEY UPDATE limits_json=VALUES(limits_json)',['plan'=>$planId,'feature'=>$featureId,'limits'=>$limits===null?null:json_encode($limits,JSON_THROW_ON_ERROR)]);}else{$this->db->execute('DELETE FROM plan_features WHERE plan_id=:plan AND feature_id=:feature',['plan'=>$planId,'feature'=>$featureId]);}
        $this->audit('plan.feature_changed','plan',$planId,['feature_id'=>$featureId,'enabled'=>$enabled,'limits'=>$limits]);
    }

    public function setSchoolOverride(int $schoolId,int $featureId,bool $enabled,?array $limits,?string $reason,?string $expiresAt):void
    {
        if($schoolId<1||$featureId<1) throw new InvalidArgumentException('Invalid school or feature.');
        $this->db->query('INSERT INTO school_feature_overrides(school_id,feature_id,enabled,limits_json,reason,expires_at) VALUES(:school,:feature,:enabled,:limits,:reason,:expires) ON DUPLICATE KEY UPDATE enabled=VALUES(enabled),limits_json=VALUES(limits_json),reason=VALUES(reason),expires_at=VALUES(expires_at)',['school'=>$schoolId,'feature'=>$featureId,'enabled'=>$enabled?1:0,'limits'=>$limits===null?null:json_encode($limits,JSON_THROW_ON_ERROR),'reason'=>$reason,'expires'=>$expiresAt?:null]);
        $this->audit('school.feature_override_changed','school',$schoolId,['feature_id'=>$featureId,'enabled'=>$enabled,'limits'=>$limits,'reason'=>$reason,'expires_at'=>$expiresAt]);
    }

    public function clearSchoolOverride(int $schoolId,int $featureId):void
    {
        $this->db->execute('DELETE FROM school_feature_overrides WHERE school_id=:school AND feature_id=:feature',['school'=>$schoolId,'feature'=>$featureId]);
        $this->audit('school.feature_override_removed','school',$schoolId,['feature_id'=>$featureId]);
    }

    public function schoolEntitlements(int $schoolId):array
    {
        return $this->db->select('SELECT f.id,f.code,f.name,f.module_code,p.code plan_code,sfo.enabled override_enabled,sfo.limits_json override_limits,sfo.reason,sfo.expires_at FROM features f LEFT JOIN school_feature_overrides sfo ON sfo.feature_id=f.id AND sfo.school_id=:school LEFT JOIN school_subscriptions ss ON ss.school_id=:school AND ss.status IN (\'trial\',\'active\') LEFT JOIN plan_features pf ON pf.plan_id=ss.plan_id AND pf.feature_id=f.id LEFT JOIN plans p ON p.id=ss.plan_id WHERE f.is_active=1 ORDER BY f.module_code,f.name',['school'=>$schoolId]);
    }

    private function audit(string $action,string $type,int $id,array $metadata):void
    {
        $actor=Session::get('platform_user_id');
        $this->db->insert('INSERT INTO platform_audit_logs(platform_user_id,action,resource_type,resource_id,metadata_json) VALUES(:actor,:action,:type,:id,:metadata)',['actor'=>$actor===null?null:(int)$actor,'action'=>$action,'type'=>$type,'id'=>$id,'metadata'=>json_encode($metadata,JSON_THROW_ON_ERROR)]);
    }
}
