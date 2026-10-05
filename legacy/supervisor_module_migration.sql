-- DFT50114 Supervisor verification module.
-- Run once against fyp_inventory_db.

CREATE TABLE IF NOT EXISTS supervisor_logbook (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    supervisor_id INT NOT NULL,
    student_id INT NOT NULL,
    week_no TINYINT UNSIGNED NOT NULL,
    is_verified TINYINT(1) NOT NULL DEFAULT 0,
    verified_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_supervisor_student_week (supervisor_id, student_id, week_no),
    KEY idx_logbook_student (student_id),
    CONSTRAINT chk_logbook_week CHECK (week_no BETWEEN 1 AND 14)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS student_demo_status (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    supervisor_id INT NOT NULL,
    student_id INT NOT NULL,
    demo_type ENUM('Demo 1', 'Demo 2') NOT NULL,
    status ENUM('Pending', 'Passed', 'Not Passed') NOT NULL DEFAULT 'Pending',
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_supervisor_student_demo (supervisor_id, student_id, demo_type),
    KEY idx_demo_student (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
