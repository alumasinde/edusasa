-- Phase 11: Timetable engine and schedule storage.
CREATE TABLE IF NOT EXISTS timetable_periods (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_id BIGINT UNSIGNED NOT NULL,
    period_no TINYINT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    starts_at TIME NULL,
    ends_at TIME NULL,
    is_break TINYINT(1) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY uq_timetable_period_school_no (school_id, period_no),
    KEY idx_timetable_period_school (school_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS timetables (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_id BIGINT UNSIGNED NOT NULL,
    academic_year_id BIGINT UNSIGNED NOT NULL,
    term_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    status ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
    generated_at DATETIME NULL,
    published_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_timetable_school_term_name (school_id, term_id, name),
    KEY idx_timetable_school_term (school_id, term_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS timetable_entries (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_id BIGINT UNSIGNED NOT NULL,
    timetable_id BIGINT UNSIGNED NOT NULL,
    class_id BIGINT UNSIGNED NOT NULL,
    stream_id BIGINT UNSIGNED NULL,
    teacher_id BIGINT UNSIGNED NOT NULL,
    subject_id BIGINT UNSIGNED NOT NULL,
    period_id BIGINT UNSIGNED NOT NULL,
    day_of_week TINYINT UNSIGNED NOT NULL,
    entry_type ENUM('lesson','double','activity','free') NOT NULL DEFAULT 'lesson',
    notes VARCHAR(500) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_tt_entry_group (school_id,timetable_id,class_id,stream_id,day_of_week,period_id),
    KEY idx_tt_entry_teacher (school_id,timetable_id,teacher_id,day_of_week,period_id),
    KEY idx_tt_entry_subject (school_id,timetable_id,subject_id),
    CONSTRAINT fk_tt_entry_timetable FOREIGN KEY (timetable_id) REFERENCES timetables(id) ON DELETE CASCADE,
    CONSTRAINT fk_tt_entry_period FOREIGN KEY (period_id) REFERENCES timetable_periods(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed standard timetable periods for every school.
-- INSERT ... SELECT ... ON DUPLICATE KEY UPDATE is avoided for MariaDB compatibility.
INSERT IGNORE INTO timetable_periods (school_id,period_no,name,starts_at,ends_at,is_break)
SELECT s.id,v.period_no,v.name,v.starts_at,v.ends_at,v.is_break
FROM schools s
CROSS JOIN (
    SELECT 1 period_no,'Period 1' name,'08:00:00' starts_at,'08:40:00' ends_at,0 is_break UNION ALL
    SELECT 2,'Period 2','08:40:00','09:20:00',0 UNION ALL
    SELECT 3,'Period 3','09:20:00','10:00:00',0 UNION ALL
    SELECT 4,'Break','10:00:00','10:30:00',1 UNION ALL
    SELECT 5,'Period 4','10:30:00','11:10:00',0 UNION ALL
    SELECT 6,'Period 5','11:10:00','11:50:00',0 UNION ALL
    SELECT 7,'Period 6','11:50:00','12:30:00',0 UNION ALL
    SELECT 8,'Lunch','12:30:00','13:30:00',1 UNION ALL
    SELECT 9,'Period 7','13:30:00','14:10:00',0 UNION ALL
    SELECT 10,'Period 8','14:10:00','14:50:00',0
) v;

-- Keep seeded period definitions current without INSERT ... ON DUPLICATE KEY UPDATE.
UPDATE timetable_periods p
INNER JOIN (
    SELECT 1 period_no,'Period 1' name,'08:00:00' starts_at,'08:40:00' ends_at,0 is_break UNION ALL
    SELECT 2,'Period 2','08:40:00','09:20:00',0 UNION ALL
    SELECT 3,'Period 3','09:20:00','10:00:00',0 UNION ALL
    SELECT 4,'Break','10:00:00','10:30:00',1 UNION ALL
    SELECT 5,'Period 4','10:30:00','11:10:00',0 UNION ALL
    SELECT 6,'Period 5','11:10:00','11:50:00',0 UNION ALL
    SELECT 7,'Period 6','11:50:00','12:30:00',0 UNION ALL
    SELECT 8,'Lunch','12:30:00','13:30:00',1 UNION ALL
    SELECT 9,'Period 7','13:30:00','14:10:00',0 UNION ALL
    SELECT 10,'Period 8','14:10:00','14:50:00',0
) v ON v.period_no=p.period_no
SET p.name=v.name,p.starts_at=v.starts_at,p.ends_at=v.ends_at,p.is_break=v.is_break;

INSERT INTO permissions (name,label,module_key) VALUES
('timetable.view','View timetables','timetable'),
('timetable.manage','Manage and generate timetables','timetable')
ON DUPLICATE KEY UPDATE label=VALUES(label),module_key=VALUES(module_key);

INSERT INTO role_permissions (role_id,permission_id)
SELECT r.id,p.id FROM roles r CROSS JOIN permissions p
WHERE r.name IN ('administrator','principal') AND p.name IN ('timetable.view','timetable.manage')
ON DUPLICATE KEY UPDATE role_id=role_id;
