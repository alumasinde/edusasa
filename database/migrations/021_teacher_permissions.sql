INSERT INTO permissions (name, description) VALUES
('teachers.view', 'View teachers and teacher assignments'),
('teachers.manage', 'Create, update and manage teachers and assignments')
ON DUPLICATE KEY UPDATE description = VALUES(description);
