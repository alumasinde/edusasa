-- Phase 5: fee structures and controlled bulk billing.
ALTER TABLE fee_structures
    ADD COLUMN target_class_id BIGINT UNSIGNED NULL AFTER term_id,
    ADD COLUMN target_stream_id BIGINT UNSIGNED NULL AFTER target_class_id;

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
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    error_message TEXT NULL,
    KEY idx_fee_batch_school (school_id),
    KEY idx_fee_batch_structure (fee_structure_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS fee_billing_batch_students (
    batch_id BIGINT UNSIGNED NOT NULL,
    student_id BIGINT UNSIGNED NOT NULL,
    invoice_id BIGINT UNSIGNED NULL,
    PRIMARY KEY (batch_id, student_id),
    KEY idx_batch_student_invoice (invoice_id)
) ENGINE=InnoDB;

-- Backfill-safe: installations where the columns already exist should skip this migration manually.
