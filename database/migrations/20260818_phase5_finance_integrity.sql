-- EduSasa Phase 5: finance integrity checks
-- Adds a stable reconciliation lookup used by automated integrity scans.
CREATE TABLE IF NOT EXISTS finance_integrity_scans (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_id BIGINT UNSIGNED NOT NULL,
    total_issues INT UNSIGNED NOT NULL DEFAULT 0,
    status ENUM('healthy','issues_found') NOT NULL DEFAULT 'healthy',
    scanned_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_finance_integrity_school (school_id, created_at)
) ENGINE=InnoDB;
INSERT INTO platform_permissions (code,name,module_code,description) VALUES
('finance.integrity','Run finance integrity checks','finance','Detect financial inconsistencies and reconciliation issues')
ON DUPLICATE KEY UPDATE name=VALUES(name),module_code=VALUES(module_code),description=VALUES(description);
