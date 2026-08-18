-- EduSasa Phase 6 Feature 5: results approval and publishing workflow.
CREATE TABLE IF NOT EXISTS examination_result_audit (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 school_id BIGINT UNSIGNED NOT NULL,
 examination_id BIGINT UNSIGNED NOT NULL,
 student_id BIGINT UNSIGNED NULL,
 action ENUM('calculated','approved','returned','published','unpublished') NOT NULL,
 from_status VARCHAR(20) NULL,
 to_status VARCHAR(20) NOT NULL,
 reason VARCHAR(500) NULL,
 performed_by BIGINT UNSIGNED NULL,
 performed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY idx_result_audit_exam (school_id,examination_id,performed_at),
 KEY idx_result_audit_student (school_id,student_id,performed_at),
 CONSTRAINT fk_result_audit_school FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
 CONSTRAINT fk_result_audit_exam FOREIGN KEY (examination_id) REFERENCES examinations(id) ON DELETE CASCADE,
 CONSTRAINT fk_result_audit_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO platform_permissions(code,name,module_code,description) VALUES
('exams.results.approve','Approve examination results','Exams','Review and approve calculated examination results'),
('exams.results.publish','Publish examination results','Exams','Publish approved examination results to permitted consumers')
ON DUPLICATE KEY UPDATE name=VALUES(name),module_code=VALUES(module_code),description=VALUES(description);
