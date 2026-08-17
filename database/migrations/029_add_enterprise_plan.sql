-- EduSasa commercial packaging: add Enterprise without changing existing plan keys.
-- Feature entitlements remain data-driven through plan_modules.

INSERT INTO plans (plan_key, name, is_active) VALUES
    ('enterprise', 'Enterprise', 1)
ON DUPLICATE KEY UPDATE name = VALUES(name), is_active = 1;

INSERT INTO plan_modules (plan_id, module_key)
SELECT p.id, m.module_key
FROM plans p
JOIN modules m ON m.module_key IN (
    'attendance', 'exams', 'fees', 'reports', 'communication',
    'parent_portal', 'student_portal', 'timetable'
)
WHERE p.plan_key = 'enterprise'
ON DUPLICATE KEY UPDATE plan_id = plan_id;
