-- Phase 5: official receipts and payment notification delivery log.
-- MySQL 8+, safe to run once after Phase 5 payment tables.

CREATE TABLE IF NOT EXISTS fee_receipts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_id BIGINT UNSIGNED NOT NULL,
    payment_id BIGINT UNSIGNED NOT NULL,
    student_id BIGINT UNSIGNED NOT NULL,
    receipt_no VARCHAR(100) NOT NULL,
    issued_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    amount DECIMAL(12,2) NOT NULL,
    currency CHAR(3) NOT NULL DEFAULT 'KES',
    method VARCHAR(80) NOT NULL,
    reference VARCHAR(190) NULL,
    payer_name VARCHAR(190) NULL,
    snapshot_json JSON NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_fee_receipts_payment (payment_id),
    UNIQUE KEY uq_fee_receipts_number (school_id, receipt_no),
    INDEX idx_fee_receipts_student_date (school_id, student_id, issued_at),
    CONSTRAINT fk_fee_receipts_school FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
    CONSTRAINT fk_fee_receipts_payment FOREIGN KEY (payment_id) REFERENCES fee_payments(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS payment_notification_deliveries (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_id BIGINT UNSIGNED NOT NULL,
    payment_id BIGINT UNSIGNED NOT NULL,
    receipt_id BIGINT UNSIGNED NULL,
    channel VARCHAR(30) NOT NULL,
    recipient VARCHAR(190) NOT NULL,
    template VARCHAR(100) NOT NULL,
    status ENUM('queued','sent','failed','skipped') NOT NULL DEFAULT 'queued',
    provider_reference VARCHAR(190) NULL,
    error_message VARCHAR(500) NULL,
    payload_json JSON NULL,
    sent_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_payment_notifications_payment (payment_id, created_at),
    INDEX idx_payment_notifications_status (status, created_at),
    CONSTRAINT fk_payment_notifications_school FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
    CONSTRAINT fk_payment_notifications_payment FOREIGN KEY (payment_id) REFERENCES fee_payments(id) ON DELETE CASCADE,
    CONSTRAINT fk_payment_notifications_receipt FOREIGN KEY (receipt_id) REFERENCES fee_receipts(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
