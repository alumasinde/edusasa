-- Platform database only. School operational data must never be added here.
CREATE TABLE IF NOT EXISTS schools (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(80) NOT NULL UNIQUE,
    name VARCHAR(190) NOT NULL,
    slug VARCHAR(190) NOT NULL UNIQUE,
    email VARCHAR(190) NULL,
    phone VARCHAR(40) NULL,
    status ENUM('pending','active','suspended','archived') NOT NULL DEFAULT 'pending',
    timezone VARCHAR(80) NOT NULL DEFAULT 'Africa/Nairobi',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_schools_status(status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS school_domains (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_id BIGINT UNSIGNED NOT NULL,
    host VARCHAR(255) NOT NULL,
    type ENUM('subdomain','custom','primary') NOT NULL DEFAULT 'primary',
    is_primary TINYINT(1) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_school_domains_host(host),
    INDEX idx_school_domains_school(school_id),
    CONSTRAINT fk_school_domains_school FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS school_databases (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_id BIGINT UNSIGNED NOT NULL,
    tenant_identifier VARCHAR(80) NOT NULL UNIQUE,
    database_name VARCHAR(64) NOT NULL UNIQUE,
    host VARCHAR(255) NOT NULL,
    port SMALLINT UNSIGNED NOT NULL DEFAULT 3306,
    username VARCHAR(190) NOT NULL,
    password_secret_ref VARCHAR(255) NOT NULL,
    status ENUM('pending','provisioning','ready','failed','disabled') NOT NULL DEFAULT 'pending',
    schema_version VARCHAR(80) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_school_databases_school(school_id),
    CONSTRAINT fk_school_databases_school FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS provisioning_jobs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_id BIGINT UNSIGNED NOT NULL,
    school_database_id BIGINT UNSIGNED NULL,
    status ENUM('pending','creating_database','migrating','seeding','creating_admin','configuring_domain','completed','failed') NOT NULL DEFAULT 'pending',
    current_step VARCHAR(80) NULL,
    retry_count INT UNSIGNED NOT NULL DEFAULT 0,
    error_reference VARCHAR(80) NULL,
    started_at DATETIME NULL,
    completed_at DATETIME NULL,
    failed_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_provisioning_school(school_id, created_at),
    INDEX idx_provisioning_status(status),
    CONSTRAINT fk_provisioning_school FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
    CONSTRAINT fk_provisioning_database FOREIGN KEY (school_database_id) REFERENCES school_databases(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS platform_settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(190) NOT NULL UNIQUE,
    setting_value TEXT NULL,
    is_encrypted TINYINT(1) NOT NULL DEFAULT 0,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
