-- EduSasa Phase 6 Feature 9: examination analytics and performance reports.
-- Uses existing published result/report-card data; no duplicate fact tables.

INSERT INTO platform_permissions
    (code, name, module_code, description)
VALUES
    ('exams.analytics.view','View examination analytics','Exams',
     'View examination performance dashboards and reports'),
    ('exams.analytics.export','Export examination analytics','Exams',
     'Export examination performance reports')
ON DUPLICATE KEY UPDATE
    name=VALUES(name),
    module_code=VALUES(module_code),
    description=VALUES(description);
