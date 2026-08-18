-- EduSasa canonical 013: examination papers and per-class subject configuration.
CREATE TABLE IF NOT EXISTS examination_papers (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 school_id BIGINT UNSIGNED NOT NULL,
 examination_id BIGINT UNSIGNED NOT NULL,
 class_id BIGINT UNSIGNED NOT NULL,
 subject_id BIGINT UNSIGNED NOT NULL,
 paper_code VARCHAR(80) NOT NULL,
 paper_name VARCHAR(160) NOT NULL,
 max_marks DECIMAL(8,2) NOT NULL,
 pass_marks DECIMAL(8,2) NOT NULL,
 weight DECIMAL(6,2) NOT NULL DEFAULT 100.00,
 duration_minutes SMALLINT UNSIGNED NULL,
 scheduled_at DATETIME NULL,
 status ENUM('draft','ready','locked') NOT NULL DEFAULT 'draft',
 instructions TEXT NULL,
 created_by BIGINT UNSIGNED NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 UNIQUE KEY uq_exam_paper_class_subject_code (examination_id,class_id,subject_id,paper_code),
 KEY idx_exam_papers_school_exam (school_id,examination_id,status),
 KEY idx_exam_papers_class (school_id,class_id,subject_id),
 CONSTRAINT fk_exam_paper_school FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
 CONSTRAINT fk_exam_paper_exam FOREIGN KEY (examination_id) REFERENCES examinations(id) ON DELETE CASCADE,
 CONSTRAINT fk_exam_paper_class FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
 CONSTRAINT fk_exam_paper_subject FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT INTO platform_permissions(code,name,module_code,description) VALUES
('exams.papers.view','View examination papers','Exams','View configured examination papers and subjects'),
('exams.papers.manage','Manage examination papers','Exams','Create, update, lock and remove examination papers')
ON DUPLICATE KEY UPDATE name=VALUES(name),module_code=VALUES(module_code),description=VALUES(description);
