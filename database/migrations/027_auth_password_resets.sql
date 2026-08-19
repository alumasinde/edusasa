-- Phase 17: password reset token storage for school users.
-- Raw reset tokens are never persisted; only SHA-256 hashes are stored.

CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_password_reset_user(school_id,user_id,created_at),
    KEY idx_password_reset_expiry(expires_at),
    CONSTRAINT fk_password_reset_school FOREIGN KEY(school_id) REFERENCES schools(id) ON DELETE CASCADE,
    CONSTRAINT fk_password_reset_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
