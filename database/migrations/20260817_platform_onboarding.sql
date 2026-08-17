-- Platform school onboarding support.
-- Stores only a hash of the one-time administrator invitation token.

CREATE TABLE IF NOT EXISTS school_admin_invitations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_id BIGINT UNSIGNED NOT NULL,
    email VARCHAR(190) NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    accepted_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_admin_invitation_school FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
    INDEX idx_admin_invitation_school (school_id),
    INDEX idx_admin_invitation_email (email),
    INDEX idx_admin_invitation_expiry (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
