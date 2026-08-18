-- Phase 10: complete the canonical teacher/teaching-assignment schema.
--
-- This migration also establishes the school-auth/RBAC tables required by
-- the existing application. Earlier canonical migrations created platform
-- RBAC, but school users/roles/permissions are consumed by Auth and by all
-- module permission migrations from Phase 10 onward.

CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_id BIGINT UNSIGNED NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(190) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    status ENUM('active','inactive','suspended') NOT NULL DEFAULT 'active',
    email_verified_at DATETIME NULL,
    deleted_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_school_email(school_id,email),
    KEY idx_users_school_status(school_id,status),
    KEY idx_users_email(email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS roles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL UNIQUE,
    code VARCHAR(80) NULL UNIQUE,
    slug VARCHAR(100) NULL UNIQUE,
    description VARCHAR(255) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS permissions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL UNIQUE,
    label VARCHAR(160) NOT NULL,
    module_key VARCHAR(100) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_permission_module_name(module_key,name),
    KEY idx_permissions_module(module_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS user_roles (
    user_id BIGINT UNSIGNED NOT NULL,
    role_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY(user_id,role_id),
    KEY idx_user_roles_role(role_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS role_permissions (
    role_id BIGINT UNSIGNED NOT NULL,
    permission_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY(role_id,permission_id),
    KEY idx_role_permissions_permission(permission_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO roles(name,code,slug,description) VALUES
    ('administrator','administrator','administrator','Full school administration access.'),
    ('principal','principal','principal','School principal access.'),
    ('teacher','teacher','teacher','Teacher access.'),
    ('parent','parent','parent','Parent portal access.'),
    ('student','student','student','Student portal access.'),
    ('school_admin','school_admin','school-admin','Full administration access within a school tenant.')
ON DUPLICATE KEY UPDATE
    code=VALUES(code), slug=VALUES(slug), description=VALUES(description), is_active=1;

ALTER TABLE teacher_subjects
    ADD COLUMN IF NOT EXISTS id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT FIRST,
    ADD COLUMN IF NOT EXISTS class_id BIGINT UNSIGNED NULL AFTER subject_id,
    ADD COLUMN IF NOT EXISTS stream_id BIGINT UNSIGNED NULL AFTER class_id,
    ADD COLUMN IF NOT EXISTS periods_per_week TINYINT UNSIGNED NULL AFTER stream_id,
    ADD COLUMN IF NOT EXISTS is_double TINYINT(1) NOT NULL DEFAULT 0 AFTER periods_per_week;

-- Keep the original teacher/subject composite primary key for compatibility
-- while guaranteeing the new surrogate id is indexed. This is intentionally
-- idempotent and also works if an interrupted earlier run already made id the
-- primary key.
ALTER TABLE teacher_subjects
    ADD UNIQUE KEY uq_teacher_subject_surrogate_id (id);

INSERT INTO permissions (name, label, module_key) VALUES
    ('teachers.view', 'View teachers & staff', 'teachers'),
    ('teachers.manage', 'Manage teachers & teaching assignments', 'teachers')
ON DUPLICATE KEY UPDATE label = VALUES(label), module_key = VALUES(module_key);

INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r CROSS JOIN permissions p
WHERE r.name IN ('administrator','principal','school_admin')
  AND p.name IN ('teachers.view','teachers.manage')
ON DUPLICATE KEY UPDATE role_id = role_id;
