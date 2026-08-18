-- EduSasa canonical 012: examination setup.
CREATE TABLE IF NOT EXISTS examinations (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 school_id BIGINT UNSIGNED NOT NULL,
 academic_year_id BIGINT UNSIGNED NOT NULL,
 term_id BIGINT UNSIGNED NOT NULL,
 name VARCHAR(160) NOT NULL,
 code VARCHAR(80) NOT NULL,
 exam_type VARCHAR(80) NOT NULL,
 starts_on DATE NOT NULL,
 ends_on DATE NOT NULL,
 status ENUM('draft','scheduled','open','closed','published','cancelled') NOT NULL DEFAULT 'draft',
 instructions TEXT NULL,
 created_by BIGINT UNSIGNED NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 UNIQUE KEY uq_exam_school_code (school_id,code),
 KEY idx_exam_school_term (school_id,term_id,status),
 KEY idx_exam_school_dates (school_id,starts_on,ends_on),
 CONSTRAINT fk_exam_school FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
 CONSTRAINT fk_exam_year FOREIGN KEY (academic_year_id) REFERENCES academic_years(id) ON DELETE RESTRICT,
 CONSTRAINT fk_exam_term FOREIGN KEY (term_id) REFERENCES terms(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS examination_classes (
 examination_id BIGINT UNSIGNED NOT NULL,
 class_id BIGINT UNSIGNED NOT NULL,
 school_id BIGINT UNSIGNED NOT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY (examination_id,class_id),
 KEY idx_exam_classes_school (school_id),
 CONSTRAINT fk_exam_classes_exam FOREIGN KEY (examination_id) REFERENCES examinations(id) ON DELETE CASCADE,
 CONSTRAINT fk_exam_classes_class FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT INTO platform_permissions (code,name,module_code,description) VALUES
('exams.view','View examinations','Exams','View examination setup and schedules'),
('exams.manage','Manage examinations','Exams','Create, update, schedule and close examinations')
ON DUPLICATE KEY UPDATE name=VALUES(name),module_code=VALUES(module_code),description=VALUES(description);