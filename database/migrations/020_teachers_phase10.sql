-- Phase 10: complete the canonical teacher/teaching-assignment schema.
ALTER TABLE teacher_subjects
    DROP PRIMARY KEY,
    ADD COLUMN IF NOT EXISTS id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT FIRST,
    ADD COLUMN IF NOT EXISTS class_id BIGINT UNSIGNED NULL AFTER subject_id,
    ADD COLUMN IF NOT EXISTS stream_id BIGINT UNSIGNED NULL AFTER class_id,
    ADD COLUMN IF NOT EXISTS periods_per_week TINYINT UNSIGNED NULL AFTER stream_id,
    ADD COLUMN IF NOT EXISTS is_double TINYINT(1) NOT NULL DEFAULT 0 AFTER periods_per_week,
    ADD PRIMARY KEY (id);

INSERT INTO permissions (name, label, module_key) VALUES
    ('teachers.view', 'View teachers & staff', 'teachers'),
    ('teachers.manage', 'Manage teachers & teaching assignments', 'teachers')
ON DUPLICATE KEY UPDATE label = VALUES(label), module_key = VALUES(module_key);

INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r CROSS JOIN permissions p
WHERE r.name IN ('administrator','principal') AND p.name IN ('teachers.view','teachers.manage')
ON DUPLICATE KEY UPDATE role_id = role_id;
