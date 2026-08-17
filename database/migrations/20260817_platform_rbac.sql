-- Platform RBAC. Separate from school-user roles.
CREATE TABLE IF NOT EXISTS platform_roles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(80) NOT NULL UNIQUE,
    name VARCHAR(120) NOT NULL,
    description VARCHAR(255) NULL,
    is_system TINYINT(1) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS platform_permissions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(120) NOT NULL UNIQUE,
    name VARCHAR(160) NOT NULL,
    module_code VARCHAR(100) NOT NULL,
    description VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_platform_permissions_module (module_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS platform_role_permissions (
    role_id BIGINT UNSIGNED NOT NULL,
    permission_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (role_id, permission_id),
    CONSTRAINT fk_prp_role FOREIGN KEY (role_id) REFERENCES platform_roles(id) ON DELETE CASCADE,
    CONSTRAINT fk_prp_permission FOREIGN KEY (permission_id) REFERENCES platform_permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS platform_user_roles (
    platform_user_id BIGINT UNSIGNED NOT NULL,
    role_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (platform_user_id, role_id),
    CONSTRAINT fk_pur_user FOREIGN KEY (platform_user_id) REFERENCES platform_users(id) ON DELETE CASCADE,
    CONSTRAINT fk_pur_role FOREIGN KEY (role_id) REFERENCES platform_roles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO platform_roles(code,name,description,is_system) VALUES
('super_admin','Super Admin','Full platform access',1),
('platform_admin','Platform Admin','Schools, plans, subscriptions and entitlements',1),
('support','Support','Read and support school operations',1),
('finance','Finance','Subscriptions and billing operations',1),
('read_only','Read Only','View-only platform access',1)
ON DUPLICATE KEY UPDATE name=VALUES(name), description=VALUES(description);

INSERT INTO platform_permissions(code,name,module_code,description) VALUES
('platform.dashboard.view','View dashboard','platform','View platform dashboard'),
('schools.view','View schools','schools','View schools'),
('schools.create','Create schools','schools','Onboard schools'),
('schools.update','Update schools','schools','Update school details'),
('schools.suspend','Suspend schools','schools','Suspend school access'),
('schools.activate','Activate schools','schools','Activate school access'),
('subscriptions.view','View subscriptions','billing','View subscriptions'),
('subscriptions.manage','Manage subscriptions','billing','Change plans and subscription state'),
('plans.view','View plans','catalog','View commercial plans'),
('plans.manage','Manage plans','catalog','Manage plans and pricing'),
('features.view','View features','catalog','View feature catalog'),
('features.manage','Manage features','catalog','Manage feature catalog'),
('entitlements.view','View entitlements','catalog','View school entitlements'),
('entitlements.manage','Manage entitlements','catalog','Change plan and school feature access'),
('platform.users.view','View platform users','access','View platform users'),
('platform.users.manage','Manage platform users','access','Create, disable and assign platform users'),
('platform.roles.view','View roles','access','View platform roles and permissions'),
('platform.roles.manage','Manage roles','access','Assign permissions to platform roles'),
('platform.audit.view','View audit log','audit','View platform audit history')
ON DUPLICATE KEY UPDATE name=VALUES(name), module_code=VALUES(module_code), description=VALUES(description);

-- Super admin receives every permission. Other roles receive least-privilege defaults.
INSERT IGNORE INTO platform_role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM platform_roles r CROSS JOIN platform_permissions p WHERE r.code='super_admin';
INSERT IGNORE INTO platform_role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM platform_roles r JOIN platform_permissions p ON p.code IN (
'platform.dashboard.view','schools.view','schools.create','schools.update','schools.suspend','schools.activate',
'subscriptions.view','subscriptions.manage','plans.view','plans.manage','features.view','features.manage',
'entitlements.view','entitlements.manage','platform.audit.view') WHERE r.code='platform_admin';
INSERT IGNORE INTO platform_role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM platform_roles r JOIN platform_permissions p ON p.code IN ('platform.dashboard.view','schools.view','schools.update','platform.audit.view') WHERE r.code='support';
INSERT IGNORE INTO platform_role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM platform_roles r JOIN platform_permissions p ON p.code IN ('platform.dashboard.view','subscriptions.view','subscriptions.manage','plans.view','platform.audit.view') WHERE r.code='finance';
INSERT IGNORE INTO platform_role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM platform_roles r JOIN platform_permissions p ON p.code LIKE '%.view' WHERE r.code='read_only';
