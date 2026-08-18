-- Phase 5: payment provider engine and M-Pesa STK Push.
-- Provider transactions are separate from fee_payments so external payment attempts
-- can remain pending/failed without creating confirmed school ledger entries.
CREATE TABLE IF NOT EXISTS payment_provider_transactions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_id BIGINT UNSIGNED NOT NULL,
    channel_id BIGINT UNSIGNED NOT NULL,
    student_id BIGINT UNSIGNED NOT NULL,
    invoice_id BIGINT UNSIGNED NULL,
    provider VARCHAR(60) NOT NULL,
    provider_reference VARCHAR(160) NULL,
    merchant_request_id VARCHAR(120) NULL,
    checkout_request_id VARCHAR(120) NULL,
    phone VARCHAR(30) NOT NULL,
    amount DECIMAL(14,2) NOT NULL,
    currency CHAR(3) NOT NULL DEFAULT 'KES',
    account_reference VARCHAR(120) NULL,
    status ENUM('initiated','pending','paid','failed','cancelled','expired') NOT NULL DEFAULT 'initiated',
    result_code VARCHAR(40) NULL,
    result_description VARCHAR(255) NULL,
    callback_token_hash CHAR(64) NOT NULL,
    request_payload JSON NULL,
    response_payload JSON NULL,
    callback_payload JSON NULL,
    paid_at DATETIME NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_provider_tx_callback_token (callback_token_hash),
    UNIQUE KEY uq_provider_checkout_request (provider, checkout_request_id),
    KEY idx_provider_tx_school_status (school_id, status),
    KEY idx_provider_tx_student (school_id, student_id),
    KEY idx_provider_tx_invoice (school_id, invoice_id),
    KEY idx_provider_tx_provider_reference (provider, provider_reference)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS payment_provider_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    transaction_id BIGINT UNSIGNED NULL,
    provider VARCHAR(60) NOT NULL,
    event_key VARCHAR(180) NOT NULL,
    payload JSON NOT NULL,
    received_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    processed_at DATETIME NULL,
    status ENUM('received','processed','failed') NOT NULL DEFAULT 'received',
    error_message VARCHAR(255) NULL,
    UNIQUE KEY uq_provider_event (provider, event_key),
    KEY idx_provider_event_transaction (transaction_id),
    KEY idx_provider_event_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO platform_permissions (code,name,module_code,description) VALUES
('finance.providers','Manage payment providers','finance','Configure and operate external payment provider integrations')
ON DUPLICATE KEY UPDATE name=VALUES(name),module_code=VALUES(module_code),description=VALUES(description);

INSERT IGNORE INTO platform_role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM platform_roles r CROSS JOIN platform_permissions p
WHERE r.code IN ('super_admin','platform_admin','finance') AND p.code='finance.providers';
