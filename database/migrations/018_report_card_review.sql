-- EduSasa Phase 6 Feature 8: report-card review, remarks and audit trail.
CREATE TABLE IF NOT EXISTS examination_report_card_reviews (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 school_id BIGINT UNSIGNED NOT NULL,
 report_card_id BIGINT UNSIGNED NOT NULL,
 action ENUM('generated','remark_updated','approved','published','returned') NOT NULL,
 actor_id BIGINT UNSIGNED NULL,
 reason VARCHAR(1000) NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY idx_report_card_review_card (school_id,report_card_id,created_at),
 CONSTRAINT fk_report_card_review_school FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
 CONSTRAINT fk_report_card_review_card FOREIGN KEY (report_card_id) REFERENCES examination_report_cards(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO platform_permissions(code,name,module_code,description) VALUES
('exams.report_cards.review','Review report cards','Exams','Review report cards and enter authorized remarks'),
('exams.report_cards.publish','Publish report cards','Exams','Publish reviewed report cards to permitted recipients')
ON DUPLICATE KEY UPDATE name=VALUES(name),module_code=VALUES(module_code),description=VALUES(description);
