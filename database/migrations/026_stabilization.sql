-- EduSasa stabilization: reconcile runtime contracts with canonical schema.

ALTER TABLE schools
    ADD COLUMN IF NOT EXISTS subdomain VARCHAR(190) NULL AFTER domain,
    ADD COLUMN IF NOT EXISTS deleted_at DATETIME NULL AFTER updated_at,
    ADD KEY IF NOT EXISTS idx_schools_subdomain(subdomain);

ALTER TABLE guardians
    ADD COLUMN IF NOT EXISTS middle_name VARCHAR(100) NULL AFTER first_name,
    ADD COLUMN IF NOT EXISTS occupation VARCHAR(160) NULL AFTER relationship,
    ADD COLUMN IF NOT EXISTS national_id VARCHAR(80) NULL AFTER occupation,
    ADD COLUMN IF NOT EXISTS status ENUM('active','inactive') NOT NULL DEFAULT 'active' AFTER national_id,
    ADD COLUMN IF NOT EXISTS user_id BIGINT UNSIGNED NULL AFTER status,
    ADD COLUMN IF NOT EXISTS deleted_at DATETIME NULL AFTER updated_at,
    ADD KEY IF NOT EXISTS idx_guardians_school_email(school_id,email),
    ADD KEY IF NOT EXISTS idx_guardians_user(user_id);

ALTER TABLE student_guardians
    ADD COLUMN IF NOT EXISTS id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT FIRST,
    ADD COLUMN IF NOT EXISTS deleted_at DATETIME NULL AFTER created_at,
    ADD COLUMN IF NOT EXISTS updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    ADD UNIQUE KEY IF NOT EXISTS uq_student_guardian_id(id);

ALTER TABLE users
    MODIFY first_name VARCHAR(100) NOT NULL DEFAULT '',
    MODIFY last_name VARCHAR(100) NOT NULL DEFAULT '',
    MODIFY password_hash VARCHAR(255) NOT NULL DEFAULT '';
