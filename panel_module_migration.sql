-- External panel QR evaluation module for DFT50114 Project Demonstration 3.
-- Run once against fyp_inventory_db.

CREATE TABLE IF NOT EXISTS panel_sessions (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    project_id INT NOT NULL,
    token CHAR(64) NOT NULL,
    created_by INT NOT NULL,
    panel_staff_id VARCHAR(30) NULL,
    panel_name VARCHAR(150) NULL,
    panel_email VARCHAR(190) NULL,
    status ENUM('Active', 'Submitted', 'Expired') NOT NULL DEFAULT 'Active',
    expires_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    submitted_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_panel_token (token),
    KEY idx_panel_project (project_id),
    CONSTRAINT fk_panel_session_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    CONSTRAINT fk_panel_session_admin FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @panel_sessions_has_staff_id = (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'panel_sessions' AND column_name = 'panel_staff_id'
);
SET @panel_sessions_staff_id_sql = IF(
    @panel_sessions_has_staff_id = 0,
    'ALTER TABLE panel_sessions ADD COLUMN panel_staff_id VARCHAR(30) NULL AFTER created_by',
    'SELECT 1'
);
PREPARE panel_sessions_staff_id_statement FROM @panel_sessions_staff_id_sql;
EXECUTE panel_sessions_staff_id_statement;
DEALLOCATE PREPARE panel_sessions_staff_id_statement;

SET @panel_sessions_has_expected_panel_count = (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'panel_sessions' AND column_name = 'expected_panel_count'
);
SET @panel_sessions_expected_panel_count_sql = IF(
    @panel_sessions_has_expected_panel_count = 0,
    'ALTER TABLE panel_sessions ADD COLUMN expected_panel_count TINYINT UNSIGNED NOT NULL DEFAULT 1 AFTER panel_staff_id',
    'SELECT 1'
);
PREPARE panel_sessions_expected_panel_count_statement FROM @panel_sessions_expected_panel_count_sql;
EXECUTE panel_sessions_expected_panel_count_statement;
DEALLOCATE PREPARE panel_sessions_expected_panel_count_statement;

CREATE TABLE IF NOT EXISTS panel_session_projects (
    panel_session_id INT UNSIGNED NOT NULL,
    project_id INT NOT NULL,
    PRIMARY KEY (panel_session_id, project_id),
    KEY idx_panel_session_project (project_id),
    CONSTRAINT fk_panel_session_projects_session FOREIGN KEY (panel_session_id) REFERENCES panel_sessions(id) ON DELETE CASCADE,
    CONSTRAINT fk_panel_session_projects_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS panel_session_choices (
    panel_session_id INT UNSIGNED NOT NULL,
    project_id INT NOT NULL,
    selected_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (panel_session_id, project_id),
    KEY idx_panel_session_choices_project (project_id),
    CONSTRAINT fk_panel_session_choices_session FOREIGN KEY (panel_session_id) REFERENCES panel_sessions(id) ON DELETE CASCADE,
    CONSTRAINT fk_panel_session_choices_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS panel_assessors (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    panel_session_id INT UNSIGNED NOT NULL,
    panel_name VARCHAR(150) NOT NULL,
    panel_email VARCHAR(190) NULL,
    status ENUM('Active', 'Submitted') NOT NULL DEFAULT 'Active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    submitted_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_panel_assessor_name (panel_session_id, panel_name),
    CONSTRAINT fk_panel_assessor_session FOREIGN KEY (panel_session_id) REFERENCES panel_sessions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS panel_evaluations (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    panel_session_id INT UNSIGNED NOT NULL,
    panel_assessor_id INT UNSIGNED NULL,
    project_id INT NOT NULL,
    panel_staff_id VARCHAR(30) NULL,
    panel_name VARCHAR(150) NOT NULL,
    panel_email VARCHAR(190) NULL,
    assessor_types VARCHAR(255) NULL,
    student_scores_json LONGTEXT NOT NULL,
    comments TEXT NULL,
    average_score DECIMAL(6,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_panel_evaluation_session_project (panel_session_id, project_id),
    KEY idx_panel_evaluation_session (panel_session_id),
    KEY idx_panel_evaluation_project (project_id),
    CONSTRAINT fk_panel_evaluation_session FOREIGN KEY (panel_session_id) REFERENCES panel_sessions(id) ON DELETE CASCADE,
    CONSTRAINT fk_panel_evaluation_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @panel_evaluations_has_staff_id = (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'panel_evaluations' AND column_name = 'panel_staff_id'
);
SET @panel_evaluations_staff_id_sql = IF(
    @panel_evaluations_has_staff_id = 0,
    'ALTER TABLE panel_evaluations ADD COLUMN panel_staff_id VARCHAR(30) NULL AFTER project_id',
    'SELECT 1'
);
PREPARE panel_evaluations_staff_id_statement FROM @panel_evaluations_staff_id_sql;
EXECUTE panel_evaluations_staff_id_statement;
DEALLOCATE PREPARE panel_evaluations_staff_id_statement;

SET @panel_evaluations_has_assessor_id = (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'panel_evaluations' AND column_name = 'panel_assessor_id'
);
SET @panel_evaluations_assessor_id_sql = IF(
    @panel_evaluations_has_assessor_id = 0,
    'ALTER TABLE panel_evaluations ADD COLUMN panel_assessor_id INT UNSIGNED NULL AFTER panel_session_id',
    'SELECT 1'
);
PREPARE panel_evaluations_assessor_id_statement FROM @panel_evaluations_assessor_id_sql;
EXECUTE panel_evaluations_assessor_id_statement;
DEALLOCATE PREPARE panel_evaluations_assessor_id_statement;

SET @panel_evaluations_has_session_index = (
    SELECT COUNT(*) FROM information_schema.statistics
    WHERE table_schema = DATABASE() AND table_name = 'panel_evaluations' AND index_name = 'idx_panel_evaluation_session'
);
SET @panel_evaluations_session_index_sql = IF(
    @panel_evaluations_has_session_index = 0,
    'ALTER TABLE panel_evaluations ADD KEY idx_panel_evaluation_session (panel_session_id)',
    'SELECT 1'
);
PREPARE panel_evaluations_session_index_statement FROM @panel_evaluations_session_index_sql;
EXECUTE panel_evaluations_session_index_statement;
DEALLOCATE PREPARE panel_evaluations_session_index_statement;

INSERT INTO panel_assessors (panel_session_id, panel_name, panel_email, status, created_at, submitted_at)
SELECT ps.id, ps.panel_name, ps.panel_email,
       IF(ps.status = 'Submitted', 'Submitted', 'Active'), ps.created_at, ps.submitted_at
FROM panel_sessions ps
WHERE ps.panel_name IS NOT NULL AND TRIM(ps.panel_name) <> ''
  AND NOT EXISTS (
      SELECT 1 FROM panel_assessors pa
      WHERE pa.panel_session_id = ps.id AND pa.panel_name = ps.panel_name
  );

UPDATE panel_evaluations pe
JOIN panel_assessors pa ON pa.panel_session_id = pe.panel_session_id AND pa.panel_name = pe.panel_name
SET pe.panel_assessor_id = pa.id
WHERE pe.panel_assessor_id IS NULL;

INSERT IGNORE INTO panel_session_choices (panel_session_id, project_id, selected_at)
SELECT latest_eval.panel_session_id, p.id, COALESCE(p.panel_choice_at, CURRENT_TIMESTAMP)
FROM projects p
JOIN (
        SELECT panel_session_id
        FROM panel_evaluations
        GROUP BY panel_session_id
        ORDER BY MAX(created_at) DESC, panel_session_id DESC
        LIMIT 1
) latest_eval
JOIN panel_session_projects psp ON psp.panel_session_id = latest_eval.panel_session_id AND psp.project_id = p.id
WHERE p.is_panel_choice = 1
    AND NOT EXISTS (
            SELECT 1 FROM panel_session_choices existing_choice
            WHERE existing_choice.project_id = p.id
    );

CREATE TABLE IF NOT EXISTS panel_student_marks (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    panel_evaluation_id INT UNSIGNED NOT NULL,
    project_id INT NOT NULL,
    student_id INT NOT NULL,
    total_score DECIMAL(6,2) NOT NULL DEFAULT 0,
    demo3_score DECIMAL(5,2) NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    UNIQUE KEY uq_panel_student_mark (panel_evaluation_id, student_id),
    KEY idx_panel_student_marks_project (project_id),
    CONSTRAINT fk_panel_student_marks_evaluation FOREIGN KEY (panel_evaluation_id) REFERENCES panel_evaluations(id) ON DELETE CASCADE,
    CONSTRAINT fk_panel_student_marks_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    CONSTRAINT fk_panel_student_marks_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Upgrade installations created with one panel evaluation per QR session.
SET @old_panel_evaluation_key = (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'panel_evaluations'
      AND index_name = 'uq_panel_evaluation_session'
);
SET @new_panel_evaluation_key = (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'panel_evaluations'
      AND index_name = 'uq_panel_eval_assessor_project'
);
SET @panel_evaluation_key_sql = IF(
    @new_panel_evaluation_key = 0,
    IF(
        (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'panel_evaluations' AND index_name = 'uq_panel_evaluation_session_project') > 0,
        'ALTER TABLE panel_evaluations DROP INDEX uq_panel_evaluation_session_project, ADD UNIQUE KEY uq_panel_eval_assessor_project (panel_assessor_id, project_id)',
        IF(
            @old_panel_evaluation_key > 0,
            'ALTER TABLE panel_evaluations DROP INDEX uq_panel_evaluation_session, ADD UNIQUE KEY uq_panel_eval_assessor_project (panel_assessor_id, project_id)',
            'ALTER TABLE panel_evaluations ADD UNIQUE KEY uq_panel_eval_assessor_project (panel_assessor_id, project_id)'
        )
    ),
    'SELECT 1'
);
PREPARE panel_evaluation_key_statement FROM @panel_evaluation_key_sql;
EXECUTE panel_evaluation_key_statement;
DEALLOCATE PREPARE panel_evaluation_key_statement;

SET @project_members_has_member_order = (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'project_members'
      AND column_name = 'member_order'
);
SET @project_members_member_order_sql = IF(
    @project_members_has_member_order = 0,
    'ALTER TABLE project_members ADD COLUMN member_order TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER role',
    'SELECT 1'
);
PREPARE project_members_member_order_statement FROM @project_members_member_order_sql;
EXECUTE project_members_member_order_statement;
DEALLOCATE PREPARE project_members_member_order_statement;

SET @projects_has_group_no = (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'projects'
      AND column_name = 'project_group_no'
);
SET @projects_group_no_sql = IF(
    @projects_has_group_no = 0,
    'ALTER TABLE projects ADD COLUMN project_group_no TINYINT UNSIGNED NULL AFTER id, ADD KEY idx_project_group_no (project_group_no)',
    'SELECT 1'
);
PREPARE projects_group_no_statement FROM @projects_group_no_sql;
EXECUTE projects_group_no_statement;
DEALLOCATE PREPARE projects_group_no_statement;

SET @projects_has_panel_choice = (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'projects'
      AND column_name = 'is_panel_choice'
);
SET @projects_panel_choice_sql = IF(
    @projects_has_panel_choice = 0,
    'ALTER TABLE projects ADD COLUMN is_panel_choice TINYINT(1) NOT NULL DEFAULT 0 AFTER project_group_no, ADD COLUMN panel_choice_at DATETIME NULL AFTER is_panel_choice, ADD KEY idx_project_panel_choice (is_panel_choice)',
    'SELECT 1'
);
PREPARE projects_panel_choice_statement FROM @projects_panel_choice_sql;
EXECUTE projects_panel_choice_statement;
DEALLOCATE PREPARE projects_panel_choice_statement;
