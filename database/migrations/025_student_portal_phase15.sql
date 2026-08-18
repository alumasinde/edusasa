-- Phase 15: secure student portal and student account ownership.
ALTER TABLE students
    ADD COLUMN IF NOT EXISTS user_id BIGINT UNSIGNED NULL;

CREATE INDEX IF NOT EXISTS idx_students_school_user ON students(school_id,user_id,status);

INSERT INTO permissions (name,label,module_key) VALUES
('studentportal.view','Access student portal','studentportal'),
('studentportal.notifications','View student notifications','studentportal'),
('studentportal.profile','Manage student profile','studentportal')
ON DUPLICATE KEY UPDATE label=VALUES(label),module_key=VALUES(module_key);

INSERT INTO role_permissions (role_id,permission_id)
SELECT r.id,p.id FROM roles r CROSS JOIN permissions p
WHERE r.name='student' AND p.name IN ('studentportal.view','studentportal.notifications','studentportal.profile')
ON DUPLICATE KEY UPDATE role_id=role_id;

INSERT INTO platform_permissions (code,name,module_code,description) VALUES
('studentportal.view','Student portal access','StudentPortal','Access the student portal'),
('studentportal.notifications','Student portal notifications','StudentPortal','View student notification history'),
('studentportal.profile','Student portal profile','StudentPortal','Manage permitted student profile fields')
ON DUPLICATE KEY UPDATE name=VALUES(name),module_code=VALUES(module_code),description=VALUES(description);
