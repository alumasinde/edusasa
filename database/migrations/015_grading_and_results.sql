-- EduSasa Phase 6 Feature 4: grading configuration and automatic result calculation.
CREATE TABLE IF NOT EXISTS assessment_grade_scales (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 school_id BIGINT UNSIGNED NOT NULL,
 name VARCHAR(120) NOT NULL,
 code VARCHAR(50) NOT NULL,
 is_default TINYINT(1) NOT NULL DEFAULT 0,
 status ENUM('active','inactive') NOT NULL DEFAULT 'active',
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 UNIQUE KEY uq_grade_scale_school_code (school_id,code),
 KEY idx_grade_scale_school_status (school_id,status),
 CONSTRAINT fk_grade_scale_school FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS assessment_grade_scale_items (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 scale_id BIGINT UNSIGNED NOT NULL,
 grade VARCHAR(20) NOT NULL,
 min_percentage DECIMAL(5,2) NOT NULL,
 max_percentage DECIMAL(5,2) NOT NULL,
 points DECIMAL(6,2) NOT NULL DEFAULT 0,
 remark VARCHAR(255) NULL,
 sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
 UNIQUE KEY uq_grade_item_scale_grade (scale_id,grade),
 KEY idx_grade_item_scale_range (scale_id,min_percentage,max_percentage),
 CONSTRAINT fk_grade_item_scale FOREIGN KEY (scale_id) REFERENCES assessment_grade_scales(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS examination_results (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 school_id BIGINT UNSIGNED NOT NULL,
 examination_id BIGINT UNSIGNED NOT NULL,
 student_id BIGINT UNSIGNED NOT NULL,
 grade_scale_id BIGINT UNSIGNED NOT NULL,
 total_marks DECIMAL(10,2) NOT NULL DEFAULT 0,
 maximum_marks DECIMAL(10,2) NOT NULL DEFAULT 0,
 percentage DECIMAL(6,2) NOT NULL DEFAULT 0,
 grade VARCHAR(20) NULL,
 points DECIMAL(8,2) NOT NULL DEFAULT 0,
 remark VARCHAR(255) NULL,
 absent_papers SMALLINT UNSIGNED NOT NULL DEFAULT 0,
 status ENUM('draft','approved','published') NOT NULL DEFAULT 'draft',
 calculated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 UNIQUE KEY uq_exam_result_student (examination_id,student_id),
 KEY idx_exam_results_school_exam (school_id,examination_id,status),
 KEY idx_exam_results_student (school_id,student_id,examination_id),
 CONSTRAINT fk_exam_result_school FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
 CONSTRAINT fk_exam_result_exam FOREIGN KEY (examination_id) REFERENCES examinations(id) ON DELETE CASCADE,
 CONSTRAINT fk_exam_result_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
 CONSTRAINT fk_exam_result_scale FOREIGN KEY (grade_scale_id) REFERENCES assessment_grade_scales(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO platform_permissions(code,name,module_code,description) VALUES
('exams.grading.view','View grading and results','Exams','View grading scales and calculated examination results'),
('exams.grading.manage','Manage grading and results','Exams','Configure grading scales and calculate examination results')
ON DUPLICATE KEY UPDATE name=VALUES(name),module_code=VALUES(module_code),description=VALUES(description);
