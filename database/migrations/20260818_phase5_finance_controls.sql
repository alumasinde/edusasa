-- EduSasa Phase 5: finance controls, period locking and reversal audit.
-- MySQL 8+, additive and tenant-safe.

CREATE TABLE IF NOT EXISTS finance_periods (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(120) NOT NULL,
    starts_on DATE NOT NULL,
    ends_on DATE NOT NULL,
    status ENUM('open','locked','closed') NOT NULL DEFAULT 'open',
    locked_by BIGINT UNSIGNED NULL,
    locked_at DATETIME NULL,
    closed_by BIGINT UNSIGNED NULL,
    closed_at DATETIME NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_finance_period_school_dates (school_id, starts_on, ends_on),
    KEY idx_finance_period_school_status (school_id, status, starts_on)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS finance_control_actions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_id BIGINT UNSIGNED NOT NULL,
    action_type ENUM('payment_reversal','invoice_void','period_lock','period_unlock','period_close','credit_balance') NOT NULL,
    payment_id BIGINT UNSIGNED NULL,
    invoice_id BIGINT UNSIGNED NULL,
    period_id BIGINT UNSIGNED NULL,
    amount DECIMAL(14,2) NULL,
    reason VARCHAR(255) NOT NULL,
    reference VARCHAR(120) NULL,
    status ENUM('requested','approved','processed','rejected') NOT NULL DEFAULT 'processed',
    requested_by BIGINT UNSIGNED NULL,
    approved_by BIGINT UNSIGNED NULL,
    processed_by BIGINT UNSIGNED NULL,
    processed_at DATETIME NULL,
    metadata JSON NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_finance_control_school (school_id, action_type, created_at),
    KEY idx_finance_control_payment (school_id, payment_id),
    KEY idx_finance_control_invoice (school_id, invoice_id),
    KEY idx_finance_control_period (school_id, period_id)
) ENGINE=InnoDB;

INSERT INTO platform_permissions (code,name,module_code,description) VALUES
('finance.controls','Manage finance controls','finance','Reverse payments, void invoices and manage financial periods'),
('finance.periods','Manage finance periods','finance','Open, lock and close finance periods')
ON DUPLICATE KEY UPDATE name=VALUES(name), module_code=VALUES(module_code), description=VALUES(description);

INSERT IGNORE INTO platform_role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM platform_roles r JOIN platform_permissions p
WHERE r.code IN ('super_admin','platform_admin','finance')
AND p.code IN ('finance.controls','finance.periods');
