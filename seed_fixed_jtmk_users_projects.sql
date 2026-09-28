-- Fixed JTMK/DFT50114 installation seed.
-- Accounts: 1 Admin + 2 Supervisors + 6 Students = 9 accounts.
-- Temporary password for all accounts: DFT50114@2026

SET FOREIGN_KEY_CHECKS = 0;
START TRANSACTION;

TRUNCATE TABLE student_demo_status;
TRUNCATE TABLE supervisor_logbook;
TRUNCATE TABLE supervisor_students;
TRUNCATE TABLE project_documents;
TRUNCATE TABLE project_marks;
TRUNCATE TABLE project_members;
TRUNCATE TABLE projects;
TRUNCATE TABLE users;

INSERT INTO users
    (username, ic_number, matric_no, full_name, email, password, role, department, program_name, course_code, track, class_name, phone_no)
VALUES
    ('771230031234', '771230031234', NULL, 'NORAZLINA BINTI ABDULLAH', 'norazlina@jtmk.local', '$2y$10$hLraNgWdEWiz/dO1vS1zZ.UvTTJGUKEoIPpGrxkWAqNSjASjpr0hK', 'Admin', 'JTMK', 'JTMK - Information Technology', 'DFT50114', NULL, NULL, NULL),
    ('881122034321', '881122034321', NULL, 'ABDUL HAKIM BIN ABDUL AZIZ', 'abdul.hakim@jtmk.local', '$2y$10$hLraNgWdEWiz/dO1vS1zZ.UvTTJGUKEoIPpGrxkWAqNSjASjpr0hK', 'Supervisor', 'JTMK', 'JTMK - Information Technology', 'DFT50114', NULL, NULL, NULL),
    ('790321031234', '790321031234', NULL, 'HARTATI BINTI MASKUR', 'hartati@jtmk.local', '$2y$10$hLraNgWdEWiz/dO1vS1zZ.UvTTJGUKEoIPpGrxkWAqNSjASjpr0hK', 'Supervisor', 'JTMK', 'JTMK - Information Technology', 'DFT50114', NULL, NULL, NULL),
    ('060319111234', '060319111234', '34DIT24F1044', 'NURUL ISMAH SYAHIRAH BINTI MOHAMMAD YUSOF', 'nurul.ismah@jtmk.local', '$2y$10$hLraNgWdEWiz/dO1vS1zZ.UvTTJGUKEoIPpGrxkWAqNSjASjpr0hK', 'Student', 'JTMK', 'JTMK - Information Technology', 'DFT50114', 'DIT', '2F', NULL),
    ('060228141234', '060228141234', '34DIT24F1032', 'NUR IZZAH ATHIRAH BINTI ABDULLAH', 'nur.izzah@jtmk.local', '$2y$10$hLraNgWdEWiz/dO1vS1zZ.UvTTJGUKEoIPpGrxkWAqNSjASjpr0hK', 'Student', 'JTMK', 'JTMK - Information Technology', 'DFT50114', 'DIT', '2F', NULL),
    ('061121061234', '061121061234', '34DIT24F1037', 'NUR ZARA ALYA BINTI ZAFRI AZRAN', 'zara.alya@jtmk.local', '$2y$10$hLraNgWdEWiz/dO1vS1zZ.UvTTJGUKEoIPpGrxkWAqNSjASjpr0hK', 'Student', 'JTMK', 'JTMK - Information Technology', 'DFT50114', 'DIT', '2F', NULL),
    ('060716141234', '060716141234', '34DIT24F1022', 'NUR SYAHIRAH BINTI MOHAMAD SAIDI', 'nur.syah@jtmk.local', '$2y$10$hLraNgWdEWiz/dO1vS1zZ.UvTTJGUKEoIPpGrxkWAqNSjASjpr0hK', 'Student', 'JTMK', 'JTMK - Information Technology', 'DFT50114', 'DIT', '2F', NULL),
    ('041122031234', '041122031234', '34DIT24F1060', 'WAN NUR ALIS SOFEA BINTI ABDUL GHANIY', 'wan.alis@jtmk.local', '$2y$10$hLraNgWdEWiz/dO1vS1zZ.UvTTJGUKEoIPpGrxkWAqNSjASjpr0hK', 'Student', 'JTMK', 'JTMK - Information Technology', 'DFT50114', 'DIT', '2F', NULL),
    ('061010101234', '061010101234', '34DIT24F1052', 'NUR HIDAYAH IZZATI BINTI RAMLI', 'nur.hidayah@jtmk.local', '$2y$10$hLraNgWdEWiz/dO1vS1zZ.UvTTJGUKEoIPpGrxkWAqNSjASjpr0hK', 'Student', 'JTMK', 'JTMK - Information Technology', 'DFT50114', 'DIT', '2F', NULL);

INSERT INTO projects
    (created_by, supervisor_id, student_id, title, department, program_name, course_code, category, session, group_password, description)
VALUES
    ((SELECT id FROM users WHERE ic_number = '060319111234'), (SELECT id FROM users WHERE ic_number = '881122034321'), (SELECT id FROM users WHERE ic_number = '060319111234'), 'JTMK Smart Information System', 'JTMK', 'JTMK - Information Technology', 'DFT50114', 'Information system', 'Session 1 2026/2027', '', 'DFT50114 group project.'),
    ((SELECT id FROM users WHERE ic_number = '060716141234'), (SELECT id FROM users WHERE ic_number = '790321031234'), (SELECT id FROM users WHERE ic_number = '060716141234'), 'JTMK Web Application Project', 'JTMK', 'JTMK - Information Technology', 'DFT50114', 'Web application', 'Session 1 2026/2027', '', 'DFT50114 group project.');

INSERT INTO project_members (project_id, student_id, role)
SELECT p.id, u.id, 'Leader'
FROM projects p
JOIN users u ON u.id = p.student_id;

INSERT INTO project_members (project_id, student_id, role)
SELECT p.id, u.id, 'Member'
FROM projects p
JOIN users u ON u.ic_number IN (
    CASE WHEN p.title = 'JTMK Smart Information System' THEN '060228141234' ELSE '041122031234' END,
    CASE WHEN p.title = 'JTMK Smart Information System' THEN '061121061234' ELSE '061010101234' END
);

INSERT INTO supervisor_students (supervisor_id, student_id, session)
SELECT s.id, u.id, 'Session 1 2026/2027'
FROM users s
JOIN users u
WHERE s.ic_number = '881122034321'
  AND u.ic_number IN ('060319111234', '060228141234', '061121061234');

INSERT INTO supervisor_students (supervisor_id, student_id, session)
SELECT s.id, u.id, 'Session 1 2026/2027'
FROM users s
JOIN users u
WHERE s.ic_number = '790321031234'
  AND u.ic_number IN ('060716141234', '041122031234', '061010101234');

COMMIT;
SET FOREIGN_KEY_CHECKS = 1;
