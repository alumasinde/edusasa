-- EduSasa Phase 5: migration alignment and permission backfill.
-- Safe to run after any existing Phase 5 migration subset.
-- This migration does not rename/delete historical migrations.

INSERT INTO platform_permissions (code,name,module_code,description) VALUES
('finance.view','View finance','finance','View finance'),
('finance.manage','Manage finance','finance','Manage fee structures and invoices'),
('finance.payments','Record and manage payments','finance','Record and manage payments'),
('finance.reports','View finance reports','finance','View finance reports'),
('finance.adjustments','Manage finance adjustments','finance','Apply discounts, waivers and credits'),
('finance.refunds','Manage finance refunds','finance','Request, approve and process refunds'),
('finance.controls','Manage finance controls','finance','Reverse payments, void invoices and manage periods'),
('finance.periods','Manage finance periods','finance','Open, lock and close finance periods'),
('finance.integrity','Run finance integrity checks','finance','Detect financial inconsistencies and reconciliation issues')
ON DUPLICATE KEY UPDATE name=VALUES(name),module_code=VALUES(module_code),description=VALUES(description);

-- Keep the platform roles aligned with the Phase 5 finance capabilities.
-- School-level authorization remains separate and is enforced by the application.
INSERT IGNORE INTO platform_role_permissions(role_id,permission_id)
SELECT r.id,p.id
FROM platform_roles r CROSS JOIN platform_permissions p
WHERE r.code='super_admin'
AND p.code IN ('finance.view','finance.manage','finance.payments','finance.reports','finance.adjustments','finance.refunds','finance.controls','finance.periods','finance.integrity');

INSERT IGNORE INTO platform_role_permissions(role_id,permission_id)
SELECT r.id,p.id
FROM platform_roles r JOIN platform_permissions p
WHERE r.code='platform_admin'
AND p.code IN ('finance.view','finance.manage','finance.payments','finance.reports','finance.adjustments','finance.refunds','finance.controls','finance.periods','finance.integrity');

INSERT IGNORE INTO platform_role_permissions(role_id,permission_id)
SELECT r.id,p.id
FROM platform_roles r JOIN platform_permissions p
WHERE r.code='finance'
AND p.code IN ('finance.view','finance.manage','finance.payments','finance.reports','finance.adjustments','finance.refunds','finance.controls','finance.periods','finance.integrity');
