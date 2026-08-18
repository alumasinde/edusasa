-- EduSasa canonical migration 000: migration registry.
-- MySQL 8+. Safe to run repeatedly.
CREATE TABLE IF NOT EXISTS schema_migrations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    migration VARCHAR(190) NOT NULL,
    checksum CHAR(64) NULL,
    applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_schema_migration (migration)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
