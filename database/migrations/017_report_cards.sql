-- EduSasa Phase 6 Feature 6: report cards and student result statements.
CREATE TABLE IF NOT EXISTS report_card_templates (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 school_id BIGINT UNSIGNED NOT NULL,
 name VARCHAR(150) NOT NULL,
 code VARCHAR(60) NOT NULL,
 header_title VARCHAR(255) NULL,
 school_motto VARCHAR(255) NULL,
 footer_text VARCHAR(500) NULL,
 logo_path VARCHAR(500) NULL,
 status ENUM('active','inactive') NOT NULL DEFAULT 'active',
 is_default TINYINT(1) NOT NULL DEFAULT 0,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 UNIQUE KEY uq_report_template_school_code (school_id,code),
 KEY idx_report_template_school_status (school_id,status),
 CONSTRAINT fk_report_template_school FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS examination_report_cards (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 school_id BIGINT UNSIGNED NOT NULL,
 examination_id BIGINT UNSIGNED NOT NULL,
 student_id BIGINT UNSIGNED NOT NULL,
 template_id BIGINT UNSIGNED NULL,
 teacher_remark VARCHAR(1000) NULL,
 principal_remark VARCHAR(1000) NULL,
 attendance_present INT UNSIGNED NOT NULL DEFAULT 0,
 attendance_absent INT UNSIGNED NOT NULL DEFAULT 0,
 attendance_late INT UNSIGNED NOT NULL DEFAULT 0,
 status ENUM('draft','generated','published') NOT NULL DEFAULT 'draft',
 generated_at DATETIME NULL,
 published_at DATETIME NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 UNIQUE KEY uq_report_card_exam_student (examination_id,student_id),
 KEY idx_report_card_school_exam_status (school_id,examination_id,status),
 CONSTRAINT fk_report_card_school FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
 CONSTRAINT fk_report_card_exam FOREIGN KEY (examination_id) REFERENCES examinations(id) ON DELETE CASCADE,
 CONSTRAINT fk_report_card_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
 CONSTRAINT fk_report_card_template FOREIGN KEY (template_id) REFERENCES report_card_templates(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS examination_report_card_subjects (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 report_card_id BIGINT UNSIGNED NOT NULL,
 subject_id BIGINT UNSIGNED NOT NULL,
 marks DECIMAL(10,2) NOT NULL DEFAULT 0,
 maximum_marks DECIMAL(10,2) NOT NULL DEFAULT 0,
 percentage DECIMAL(6,2) NOT NULL DEFAULT 0,
 grade VARCHAR(20) NULL,
 points DECIMAL(8,2) NOT NULL DEFAULT 0,
 remark VARCHAR(255) NULL,
 is_absent TINYINT(1) NOT NULL DEFAULT 0,
 UNIQUE KEY uq_report_card_subject (report_card_id,subject_id),
 KEY idx_report_card_subject_subject (subject_id),
 CONSTRAINT fk_report_card_subject_card FOREIGN KEY (report_card_id) REFERENCES examination_report_cards(id) ON DELETE CASCADE,
 CONSTRAINT fk_report_card_subject_subject FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO platform_permissions(code,name,module_code,description) VALUES
('exams.report_cards.view','View report cards','Exams','View generated student report cards and statements'),
('exams.report_cards.manage','Manage report cards','Exams','Generate, publish and manage student report cards')
ON DUPLICATE KEY UPDATE name=VALUES(name),module_code=VALUES(module_code),description=VALUES(description);
