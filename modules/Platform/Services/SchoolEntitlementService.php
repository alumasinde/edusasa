<?php

declare(strict_types=1);

namespace Modules\Platform\Services;

use App\Core\Database;

final class SchoolEntitlementService
{
    public function __construct(private readonly Database $db) {}

    /** Resolve effective plan features and school-specific overrides. */
    public function resolve(int $schoolId): array
    {
        if ($schoolId < 1) return ['features' => [], 'limits' => []];

        $rows = $this->db->select(
            "SELECT f.code,pf.limits_json AS plan_limits,
                    sfo.enabled AS override_enabled,sfo.limits_json AS override_limits
             FROM school_subscriptions ss
             INNER JOIN plan_features pf ON pf.plan_id=ss.plan_id
             INNER JOIN features f ON f.id=pf.feature_id AND f.is_active=1
             LEFT JOIN school_feature_overrides sfo
               ON sfo.school_id=ss.school_id AND sfo.feature_id=f.id
              AND (sfo.expires_at IS NULL OR sfo.expires_at>NOW())
             WHERE ss.school_id=:school_id
               AND ss.status IN ('trial','active')
               AND (ss.ends_at IS NULL OR ss.ends_at>NOW())
             ORDER BY f.code",
            ['school_id'=>$schoolId]
        );

        $features=[];
        $limits=[];
        foreach ($rows as $row) {
            $code=(string)$row['code'];
            $features[$code]=$row['override_enabled']===null || (int)$row['override_enabled']===1;
            $json=$row['override_enabled']!==null ? $row['override_limits'] : $row['plan_limits'];
            if ($json!==null && $json!=='') {
                $decoded=json_decode((string)$json,true);
                if (is_array($decoded)) $limits[$code]=$decoded;
            }
        }

        // Overrides may enable a feature not included in the plan.
        $overrides=$this->db->select(
            "SELECT f.code,sfo.enabled,sfo.limits_json
             FROM school_feature_overrides sfo
             INNER JOIN features f ON f.id=sfo.feature_id AND f.is_active=1
             WHERE sfo.school_id=:school_id
               AND (sfo.expires_at IS NULL OR sfo.expires_at>NOW())",
            ['school_id'=>$schoolId]
        );
        foreach ($overrides as $row) {
            $code=(string)$row['code'];
            $features[$code]=(int)$row['enabled']===1;
            if ($row['limits_json']!==null) {
                $decoded=json_decode((string)$row['limits_json'],true);
                if (is_array($decoded)) $limits[$code]=$decoded;
            }
        }

        return ['features'=>$features,'limits'=>$limits];
    }
}
