<?php

declare(strict_types=1);

namespace Modules\Platform\Services;

use App\Core\Database;

final class EntitlementService
{
    public function __construct(private readonly Database $db) {}

    public function enabled(int $schoolId, string $featureCode): bool
    {
        $row = $this->db->selectOne(
            'SELECT f.id, COALESCE(o.enabled, 1) AS override_enabled
             FROM features f
             INNER JOIN plan_features pf ON pf.feature_id=f.id
             INNER JOIN school_subscriptions ss ON ss.plan_id=pf.plan_id
                AND ss.school_id=:school_id
                AND ss.status IN (\'trial\',\'active\')
             LEFT JOIN school_feature_overrides o ON o.school_id=ss.school_id AND o.feature_id=f.id
                AND (o.expires_at IS NULL OR o.expires_at > NOW())
             WHERE f.code=:feature_code AND f.is_active=1
             ORDER BY ss.id DESC LIMIT 1',
            ['school_id' => $schoolId, 'feature_code' => $featureCode]
        );
        return $row !== null && (int) $row['override_enabled'] === 1;
    }

    public function limits(int $schoolId, string $featureCode): array
    {
        $row = $this->db->selectOne(
            'SELECT COALESCE(o.limits_json, pf.limits_json) AS limits_json
             FROM features f
             INNER JOIN plan_features pf ON pf.feature_id=f.id
             INNER JOIN school_subscriptions ss ON ss.plan_id=pf.plan_id
                AND ss.school_id=:school_id AND ss.status IN (\'trial\',\'active\')
             LEFT JOIN school_feature_overrides o ON o.school_id=ss.school_id AND o.feature_id=f.id
                AND (o.expires_at IS NULL OR o.expires_at > NOW())
             WHERE f.code=:feature_code AND f.is_active=1
             ORDER BY ss.id DESC LIMIT 1',
            ['school_id' => $schoolId, 'feature_code' => $featureCode]
        );
        if ($row === null || empty($row['limits_json'])) return [];
        $decoded = json_decode((string) $row['limits_json'], true);
        return is_array($decoded) ? $decoded : [];
    }

    public function activePlan(int $schoolId): ?array
    {
        return $this->db->selectOne(
            'SELECT p.id,p.code,p.name,p.price,p.billing_interval,ss.status,ss.starts_at,ss.trial_ends_at,ss.renews_at,ss.ends_at
             FROM school_subscriptions ss INNER JOIN plans p ON p.id=ss.plan_id
             WHERE ss.school_id=:school_id AND ss.status IN (\'trial\',\'active\')
             ORDER BY ss.id DESC LIMIT 1',
            ['school_id' => $schoolId]
        );
    }
}
