-- DFT50114 module fields for existing installations.
-- Run once against fyp_inventory_db before using the enhanced modules.

ALTER TABLE users
    ADD COLUMN matric_no VARCHAR(30) NULL AFTER ic_number,
    ADD COLUMN track VARCHAR(100) NULL AFTER department,
    ADD COLUMN class_name VARCHAR(100) NULL AFTER track,
    ADD COLUMN phone_no VARCHAR(30) NULL AFTER class_name;

ALTER TABLE project_documents
    MODIFY COLUMN doc_type VARCHAR(50) NOT NULL,
    ADD COLUMN original_name VARCHAR(255) NULL AFTER file_path,
    ADD COLUMN status ENUM('Pending', 'Reviewed', 'Approved', 'Rejected') NOT NULL DEFAULT 'Pending' AFTER original_name;

ALTER TABLE project_marks
    ADD COLUMN proposal_presentation DECIMAL(5,2) NOT NULL DEFAULT 0 AFTER project_id,
    ADD COLUMN demonstration_1 DECIMAL(5,2) NOT NULL DEFAULT 0 AFTER proposal_presentation,
    ADD COLUMN demonstration_2 DECIMAL(5,2) NOT NULL DEFAULT 0 AFTER demonstration_1,
    ADD COLUMN demonstration_3 DECIMAL(5,2) NOT NULL DEFAULT 0 AFTER demonstration_2,
    ADD COLUMN final_poster DECIMAL(5,2) NOT NULL DEFAULT 0 AFTER demonstration_3,
    ADD COLUMN final_presentation DECIMAL(5,2) NOT NULL DEFAULT 0 AFTER final_poster,
    ADD COLUMN log_book DECIMAL(5,2) NOT NULL DEFAULT 0 AFTER final_presentation,
    ADD COLUMN technical_report DECIMAL(5,2) NOT NULL DEFAULT 0 AFTER log_book;
