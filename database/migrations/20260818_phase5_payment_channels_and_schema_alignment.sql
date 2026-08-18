-- Phase 5: school-configurable payment channels and migration alignment.
CREATE TABLE IF NOT EXISTS school_payment_channels (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_id BIGINT UNSIGNED NOT NULL,
    code VARCHAR(60) NOT NULL,
    name VARCHAR(120) NOT NULL,
    type ENUM('mpesa','bank','cash','cheque','other') NOT NULL DEFAULT 'other',
    provider VARCHAR(100) NULL,
    config_json JSON NULL,
    instructions TEXT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    is_default TINYINT(1) NOT NULL DEFAULT 0,
    sort_order INT NOT NULL DEFAULT 0,
    allow_parent_payment TINYINT(1) NOT NULL DEFAULT 1,
    allow_staff_entry TINYINT(1) NOT NULL DEFAULT 1,
    requires_reference TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_school_payment_channel (school_id, code),
    KEY idx_school_payment_channel_active (school_id, is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS fee_reconciliations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_id BIGINT UNSIGNED NOT NULL,
    reconciliation_date DATE NOT NULL,
    method VARCHAR(60) NOT NULL,
    expected_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
    actual_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
    variance DECIMAL(14,2) NOT NULL DEFAULT 0,
    status ENUM('open','reconciled','void') NOT NULL DEFAULT 'open',
    notes TEXT NULL,
    reconciled_by BIGINT UNSIGNED NULL,
    reconciled_at DATETIME NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_reconciliation_scope (school_id, reconciliation_date, method),
    KEY idx_reconciliation_school_date (school_id, reconciliation_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS fee_billing_batches (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_id BIGINT UNSIGNED NOT NULL,
    fee_structure_id BIGINT UNSIGNED NOT NULL,
    invoice_date DATE NOT NULL,
    due_date DATE NULL,
    invoice_prefix VARCHAR(40) NOT NULL,
    students_targeted INT UNSIGNED NOT NULL DEFAULT 0,
    invoices_created INT UNSIGNED NOT NULL DEFAULT 0,
    total_billed DECIMAL(14,2) NOT NULL DEFAULT 0,
    status ENUM('running','completed','failed') NOT NULL DEFAULT 'running',
    created_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME NULL,
    error_message TEXT NULL,
    KEY idx_fee_batch_school (school_id),
    KEY idx_fee_batch_structure (fee_structure_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS fee_billing_batch_students (
    batch_id BIGINT UNSIGNED NOT NULL,
    student_id BIGINT UNSIGNED NOT NULL,
    invoice_id BIGINT UNSIGNED NULL,
    PRIMARY KEY (batch_id, student_id),
    KEY idx_batch_student_invoice (invoice_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET @sql = IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='fee_structures' AND COLUMN_NAME='target_class_id')=0,
    'ALTER TABLE fee_structures ADD COLUMN target_class_id BIGINT UNSIGNED NULL AFTER term_id', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql = IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='fee_structures' AND COLUMN_NAME='target_stream_id')=0,
    'ALTER TABLE fee_structures ADD COLUMN target_stream_id BIGINT UNSIGNED NULL AFTER target_class_id', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- This migration is the canonical Phase 5 repair point for installations where earlier
-- feature migrations were applied out of order. Existing data is preserved.
