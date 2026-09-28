-- JTMK / IT scope migration for DFT50114.
-- Run once against fyp_inventory_db.

-- Existing project records are already JTMK in this installation.
UPDATE users SET department = 'JTMK' WHERE department IS NULL OR department <> 'JTMK';
UPDATE projects SET department = 'JTMK' WHERE department <> 'JTMK';
UPDATE projects SET category = 'Web application' WHERE category = 'WEB BASED SYSTEM';

ALTER TABLE users
    MODIFY department ENUM('JTMK') NOT NULL DEFAULT 'JTMK',
    ADD COLUMN program_name VARCHAR(120) NOT NULL DEFAULT 'JTMK - Information Technology' AFTER department,
    ADD COLUMN course_code VARCHAR(20) NOT NULL DEFAULT 'DFT50114' AFTER program_name;

ALTER TABLE projects
    MODIFY department ENUM('JTMK') NOT NULL DEFAULT 'JTMK',
    MODIFY group_password VARCHAR(100) NULL DEFAULT NULL,
    ADD COLUMN program_name VARCHAR(120) NOT NULL DEFAULT 'JTMK - Information Technology' AFTER department,
    ADD COLUMN course_code VARCHAR(20) NOT NULL DEFAULT 'DFT50114' AFTER program_name,
    MODIFY category ENUM(
        'Multimedia and animation',
        'Internet of Things (IOT)',
        'Artificial Intelligent (AI)',
        'Software application',
        'Web application',
        'Mobile application',
        'Networking system',
        'Hardware design',
        'Robotic programming',
        'Information system',
        'Security system',
        'Data management & visualization',
        'Data analysis'
    ) NOT NULL;
