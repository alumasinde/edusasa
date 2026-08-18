-- Phase 5: daily payment reconciliation and controlled cash-up.
CREATE TABLE IF NOT EXISTS fee_reconciliations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_id BIGINT UNSIGNED NOT NULL,
    reconciliation_date DATE NOT NULL,
    method VARCHAR(40) NOT NULL,
    expected_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
    actual_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
    variance DECIMAL(14,2) NOT NULL DEFAULT 0,
    status ENUM('open','reconciled','void') NOT NULL DEFAULT 'open',
    notes TEXT NULL,
    reconciled_by BIGINT UNSIGNED NULL,
    reconciled_at TIMESTAMP NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_reconciliation_scope (school_id, reconciliation_date, method),
    KEY idx_reconciliation_school_date (school_id, reconciliation_date)
) ENGINE=InnoDB;
