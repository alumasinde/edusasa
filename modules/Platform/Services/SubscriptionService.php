<?php

declare(strict_types=1);

namespace Modules\Platform\Services;

use App\Core\Database;
use InvalidArgumentException;

final class SubscriptionService
{
    public function __construct(private readonly Database $db) {}

    public function current(int $schoolId): ?array
    {
        return $this->db->selectOne(
            'SELECT ss.id,ss.school_id,ss.plan_id,p.code AS plan_code,p.name AS plan_name,p.price,p.billing_interval,
                    ss.status,ss.starts_at,ss.trial_ends_at,ss.renews_at,ss.ends_at,ss.external_reference
             FROM school_subscriptions ss INNER JOIN plans p ON p.id=ss.plan_id
             WHERE ss.school_id=:school_id ORDER BY ss.id DESC LIMIT 1',
            ['school_id'=>$schoolId]
        );
    }

    public function changePlan(int $schoolId, string $planCode, ?string $renewsAt = null): int
    {
        $plan=$this->db->selectOne('SELECT id,code,name FROM plans WHERE code=:code AND is_active=1',['code'=>trim($planCode)]);
        if ($plan===null) throw new InvalidArgumentException('Selected plan is not available.');
        $current=$this->current($schoolId);
        return (int)$this->db->transaction(function(Database $db) use ($schoolId,$plan,$current,$renewsAt): string {
            if ($current!==null && in_array($current['status'],['trial','active','past_due'],true)) {
                $db->execute("UPDATE school_subscriptions SET status='cancelled', ends_at=NOW() WHERE id=:id",['id'=>$current['id']]);
            }
            $id=$db->insert(
                "INSERT INTO school_subscriptions(school_id,plan_id,status,starts_at,renews_at)
                 VALUES(:school_id,:plan_id,'active',NOW(),:renews_at)",
                ['school_id'=>$schoolId,'plan_id'=>$plan['id'],'renews_at'=>$renewsAt]
            );
            $db->insert(
                'INSERT INTO platform_audit_logs(action,resource_type,resource_id,school_id,metadata_json,created_at)
                 VALUES(:action,:type,:id,:school_id,:metadata,NOW())',
                ['action'=>'subscription.plan_changed','type'=>'subscription','id'=>$id,'school_id'=>$schoolId,
                 'metadata'=>json_encode(['plan_code'=>$plan['code'],'previous_subscription_id'=>$current['id']??null],JSON_THROW_ON_ERROR)]
            );
            return $id;
        });
    }

    public function setStatus(int $subscriptionId, string $status): void
    {
        $allowed=['trial','active','past_due','suspended','cancelled','expired'];
        if (!in_array($status,$allowed,true)) throw new InvalidArgumentException('Invalid subscription status.');
        $subscription=$this->db->selectOne('SELECT id,school_id,status FROM school_subscriptions WHERE id=:id',['id'=>$subscriptionId]);
        if ($subscription===null) throw new InvalidArgumentException('Subscription not found.');
        $this->db->transaction(function(Database $db) use ($subscription,$status): void {
            $db->execute('UPDATE school_subscriptions SET status=:status WHERE id=:id',['status'=>$status,'id'=>$subscription['id']]);
            $db->insert(
                'INSERT INTO platform_audit_logs(action,resource_type,resource_id,school_id,metadata_json,created_at)
                 VALUES(:action,:type,:id,:school_id,:metadata,NOW())',
                ['action'=>'subscription.status_changed','type'=>'subscription','id'=>$subscription['id'],'school_id'=>$subscription['school_id'],
                 'metadata'=>json_encode(['from'=>$subscription['status'],'to'=>$status],JSON_THROW_ON_ERROR)]
            );
        });
    }

    public function list(array $filters=[]): array
    {
        $where=[];$params=[];
        if (!empty($filters['status'])) {$where[]='ss.status=:status';$params['status']=$filters['status'];}
        if (!empty($filters['search'])) {$where[]='(s.name LIKE :search OR s.code LIKE :search OR p.name LIKE :search)';$params['search']='%'.trim($filters['search']).'%';}
        $sql='SELECT ss.id,ss.school_id,s.name AS school_name,s.code AS school_code,p.code AS plan_code,p.name AS plan_name,
                     p.price,p.billing_interval,ss.status,ss.starts_at,ss.trial_ends_at,ss.renews_at,ss.ends_at
              FROM school_subscriptions ss INNER JOIN schools s ON s.id=ss.school_id INNER JOIN plans p ON p.id=ss.plan_id';
        if($where)$sql.=' WHERE '.implode(' AND ',$where);
        return $this->db->select($sql.' ORDER BY ss.id DESC LIMIT 200',$params);
    }
}
