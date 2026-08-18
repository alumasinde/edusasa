-- EduSasa Phase 5: credits, discounts, waivers and refunds.
-- MySQL 8+, additive and tenant-safe.

CREATE TABLE IF NOT EXISTS fee_adjustments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_id BIGINT UNSIGNED NOT NULL,
    student_id BIGINT UNSIGNED NOT NULL,
    invoice_id BIGINT UNSIGNED NOT NULL,
    type ENUM('discount','waiver','credit') NOT NULL,
    amount DECIMAL(14,2) NOT NULL,
    reason VARCHAR(255) NOT NULL,
    reference VARCHAR(120) NULL,
    status ENUM('approved','voided') NOT NULL DEFAULT 'approved',
    approved_by BIGINT UNSIGNED NULL,
    approved_at DATETIME NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_fee_adjustment_school FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
    CONSTRAINT fk_fee_adjustment_student FOREIGN KEY (student_id) REFERENCES students(id),
    CONSTRAINT fk_fee_adjustment_invoice FOREIGN KEY (invoice_id) REFERENCES fee_invoices(id),
    INDEX idx_fee_adjustment_invoice (school_id, invoice_id, status),
    INDEX idx_fee_adjustment_student (school_id, student_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS fee_refunds (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_id BIGINT UNSIGNED NOT NULL,
    payment_id BIGINT UNSIGNED NOT NULL,
    student_id BIGINT UNSIGNED NOT NULL,
    invoice_id BIGINT UNSIGNED NULL,
    amount DECIMAL(14,2) NOT NULL,
    reason VARCHAR(255) NOT NULL,
    reference VARCHAR(120) NULL,
    status ENUM('requested','approved','processed','rejected','cancelled') NOT NULL DEFAULT 'requested',
    requested_by BIGINT UNSIGNED NULL,
    approved_by BIGINT UNSIGNED NULL,
    processed_by BIGINT UNSIGNED NULL,
    processed_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_fee_refund_school FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
    CONSTRAINT fk_fee_refund_payment FOREIGN KEY (payment_id) REFERENCES fee_payments(id),
    CONSTRAINT fk_fee_refund_student FOREIGN KEY (student_id) REFERENCES students(id),
    CONSTRAINT fk_fee_refund_invoice FOREIGN KEY (invoice_id) REFERENCES fee_invoices(id),
    INDEX idx_fee_refund_school_status (school_id, status, created_at),
    INDEX idx_fee_refund_payment (school_id, payment_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO permissions (code,name,module) VALUES
('finance.adjustments','Manage fee adjustments','Finance'),
('finance.refunds','Manage payment refunds','Finance');
