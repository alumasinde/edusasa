-- Phase 16: school administration and settings foundation.
ALTER TABLE schools ADD COLUMN IF NOT EXISTS address VARCHAR(255) NULL;
ALTER TABLE schools ADD COLUMN IF NOT EXISTS logo_url VARCHAR(500) NULL;

INSERT INTO permissions (name,label,module_key) VALUES
('settings.view','View school settings','settings'),
('settings.manage','Manage school settings','settings'),
('settings.audit','View school audit log','settings')
ON DUPLICATE KEY UPDATE label=VALUES(label),module_key=VALUES(module_key);

INSERT INTO platform_permissions (code,name,module_code,description) VALUES
('settings.view','View school settings','Settings','View school configuration'),
('settings.manage','Manage school settings','Settings','Update school configuration'),
('settings.audit','View school audit log','Settings','View school audit activity')
ON DUPLICATE KEY UPDATE name=VALUES(name),module_code=VALUES(module_code),description=VALUES(description);

CREATE INDEX IF NOT EXISTS idx_platform_audit_school_created ON platform_audit_logs(school_id,created_at,id);
