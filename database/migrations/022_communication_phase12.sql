-- Phase 12: school communication, announcements and notification inbox.
CREATE TABLE IF NOT EXISTS communications (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 school_id BIGINT UNSIGNED NOT NULL,
 sender_user_id BIGINT UNSIGNED NULL,
 title VARCHAR(190) NOT NULL,
 body TEXT NOT NULL,
 type ENUM('announcement','message','notice') NOT NULL DEFAULT 'announcement',
 audience_type ENUM('all','role','class','stream','user') NOT NULL DEFAULT 'all',
 audience_role VARCHAR(80) NULL,
 audience_class_id BIGINT UNSIGNED NULL,
 audience_stream_id BIGINT UNSIGNED NULL,
 audience_user_id BIGINT UNSIGNED NULL,
 status ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
 published_at DATETIME NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 KEY idx_communication_school_status(school_id,status,created_at),
 KEY idx_communication_audience(school_id,audience_type,audience_class_id,audience_stream_id),
 CONSTRAINT fk_communication_school FOREIGN KEY(school_id) REFERENCES schools(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS communication_recipients (
 communication_id BIGINT UNSIGNED NOT NULL,
 user_id BIGINT UNSIGNED NOT NULL,
 delivered_at DATETIME NULL,
 read_at DATETIME NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY(communication_id,user_id),
 KEY idx_communication_recipient_user(user_id,read_at,created_at),
 CONSTRAINT fk_communication_recipient_message FOREIGN KEY(communication_id) REFERENCES communications(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS communication_templates (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 school_id BIGINT UNSIGNED NOT NULL,
 name VARCHAR(150) NOT NULL,
 type ENUM('announcement','message','notice') NOT NULL DEFAULT 'announcement',
 subject VARCHAR(190) NOT NULL,
 body TEXT NOT NULL,
 is_active TINYINT(1) NOT NULL DEFAULT 1,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 UNIQUE KEY uq_communication_template_school_name(school_id,name),
 KEY idx_communication_template_school(school_id),
 CONSTRAINT fk_communication_template_school FOREIGN KEY(school_id) REFERENCES schools(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO permissions (name,label,module_key) VALUES
('communication.view','View communication','communication'),
('communication.manage','Manage communication','communication'),
('communication.send','Send communication','communication'),
('communication.inbox','View notification inbox','communication')
ON DUPLICATE KEY UPDATE label=VALUES(label),module_key=VALUES(module_key);

INSERT INTO role_permissions (role_id,permission_id)
SELECT r.id,p.id FROM roles r CROSS JOIN permissions p
WHERE r.name IN ('administrator','principal') AND p.name IN ('communication.view','communication.manage','communication.send','communication.inbox')
ON DUPLICATE KEY UPDATE role_id=role_id;

INSERT INTO role_permissions (role_id,permission_id)
SELECT r.id,p.id FROM roles r CROSS JOIN permissions p
WHERE r.name IN ('teacher','parent','student') AND p.name='communication.inbox'
ON DUPLICATE KEY UPDATE role_id=role_id;
