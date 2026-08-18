-- Phase 14: parent portal foundation and secure guardian access.
ALTER TABLE guardians
    ADD COLUMN IF NOT EXISTS user_id BIGINT UNSIGNED NULL,
    ADD COLUMN IF NOT EXISTS middle_name VARCHAR(100) NULL,
    ADD COLUMN IF NOT EXISTS occupation VARCHAR(160) NULL,
    ADD COLUMN IF NOT EXISTS national_id VARCHAR(80) NULL,
    ADD COLUMN IF NOT EXISTS deleted_at DATETIME NULL;

ALTER TABLE student_guardians
    ADD COLUMN IF NOT EXISTS id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT FIRST,
    ADD COLUMN IF NOT EXISTS deleted_at DATETIME NULL,
    ADD UNIQUE KEY uq_student_guardian_id (id);

CREATE INDEX IF NOT EXISTS idx_guardians_school_user ON guardians(school_id,user_id);
CREATE INDEX IF NOT EXISTS idx_student_guardians_guardian_active ON student_guardians(guardian_id,deleted_at);

INSERT INTO permissions (name,label,module_key) VALUES
('parentportal.view','Access parent portal','parentportal'),
('parentportal.notifications','View parent notifications','parentportal'),
('parentportal.profile','Manage parent profile','parentportal')
ON DUPLICATE KEY UPDATE label=VALUES(label),module_key=VALUES(module_key);

INSERT INTO role_permissions (role_id,permission_id)
SELECT r.id,p.id FROM roles r CROSS JOIN permissions p
WHERE r.name='parent' AND p.name IN ('parentportal.view','parentportal.notifications','parentportal.profile')
ON DUPLICATE KEY UPDATE role_id=role_id;

INSERT INTO platform_permissions (code,name,module_code,description) VALUES
('parentportal.view','Parent portal access','ParentPortal','Access the parent portal'),
('parentportal.notifications','Parent portal notifications','ParentPortal','View parent notification history')
ON DUPLICATE KEY UPDATE name=VALUES(name),module_code=VALUES(module_code),description=VALUES(description);
