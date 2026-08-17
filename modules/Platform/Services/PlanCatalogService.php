<?php

declare(strict_types=1);

namespace Modules\Platform\Services;

use App\Core\Database;
use InvalidArgumentException;

final class PlanCatalogService
{
    public function __construct(private readonly Database $db) {}

    public function plans(bool $activeOnly = true): array
    {
        $where = $activeOnly ? 'WHERE p.is_active=1' : '';
        return $this->db->select(
            "SELECT p.id,p.code,p.name,p.description,p.price,p.billing_interval,p.is_active,p.sort_order,
                    COUNT(pf.feature_id) AS feature_count
             FROM plans p LEFT JOIN plan_features pf ON pf.plan_id=p.id
             $where GROUP BY p.id ORDER BY p.sort_order,p.name"
        );
    }

    public function findByCode(string $code): ?array
    {
        $code = trim($code);
        if ($code === '') return null;
        return $this->db->selectOne(
            'SELECT id,code,name,description,price,billing_interval,is_active,sort_order FROM plans WHERE code=:code AND is_active=1',
            ['code' => $code]
        );
    }

    public function features(?string $module = null): array
    {
        if ($module !== null && trim($module) !== '') {
            return $this->db->select(
                'SELECT id,code,name,module_code,description,is_active FROM features WHERE module_code=:module ORDER BY name',
                ['module'=>trim($module)]
            );
        }
        return $this->db->select('SELECT id,code,name,module_code,description,is_active FROM features ORDER BY module_code,name');
    }

    public function setFeature(int $planId, int $featureId, bool $enabled, ?array $limits = null): void
    {
        if ($planId < 1 || $featureId < 1) throw new InvalidArgumentException('Invalid plan or feature.');
        if ($enabled) {
            $this->db->query(
                'INSERT INTO plan_features(plan_id,feature_id,limits_json) VALUES(:plan,:feature,:limits)
                 ON DUPLICATE KEY UPDATE limits_json=VALUES(limits_json)',
                ['plan'=>$planId,'feature'=>$featureId,'limits'=>$limits === null ? null : json_encode($limits, JSON_THROW_ON_ERROR)]
            );
            return;
        }
        $this->db->execute('DELETE FROM plan_features WHERE plan_id=:plan AND feature_id=:feature', ['plan'=>$planId,'feature'=>$featureId]);
    }
}
