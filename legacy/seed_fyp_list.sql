-- Additive, repeatable import for the supplied DFT50114 project list.
-- Missing IC values are synthetic 12-digit placeholders with alternating 06/05 prefixes.
-- Short matric values such as F1014 are normalized to 34DIT24F1014.

SET NAMES utf8mb4;
START TRANSACTION;

DROP TEMPORARY TABLE IF EXISTS import_fyp_students;
CREATE TEMPORARY TABLE import_fyp_students (
    group_no TINYINT NOT NULL,
    member_no TINYINT NOT NULL,
    matric_no VARCHAR(30) NOT NULL,
    full_name VARCHAR(150) NOT NULL,
    class_name VARCHAR(100) NULL,
    placeholder_ic CHAR(12) NOT NULL,
    PRIMARY KEY (group_no, member_no),
    UNIQUE KEY uq_import_fyp_matric (matric_no)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO import_fyp_students (group_no, member_no, matric_no, full_name, class_name, placeholder_ic) VALUES
(1, 1, '34DIT34F1051', 'ZARIF ZAKIR BIN MOHD RASYDAN', 'DIT5B', '060101000001'),
(1, 2, '34DIT24F1017', 'MUHAMMAD AIMAN BIN YUSOF', 'DIT5B', '050101000002'),
(1, 3, '34DIT24F1013', 'MUHAMMAD ADAM DARWISY BIN AZMAN', 'DIT5B', '060101000003'),
(2, 1, '34DIT24F1010', 'NUR FATIN AMIRAH BINTI SHUKRI', 'DIT5A', '050101000004'),
(2, 2, '34DIT24F1046', 'NURHAFAZIRA ALYA BINTI MUHAMMAD FAUZI', 'DIT5A', '060101000005'),
(2, 3, '34DIT24F1061', 'NURUL HIDAYAH BINTI MOHD ANUAR', 'DIT5B', '050101000006'),
(3, 1, '34DIT24F1014', 'ARIF DANIAL BIN MOHAMMAD', 'DIT5A', '060101000007'),
(3, 2, '34DIT24F1036', 'YUKNAN KHOMANI A/L PRASET', 'DIT5A', '050101000008'),
(3, 3, '34DIT24F1020', 'MUHAMMAD HARRAZ AYMAN BIN AHMAD NIZAM', 'DIT5A', '060101000009'),
(4, 1, '34DIT24F1047', 'SHARIFAH AMNIE WAFIAH BINTI SYED SAIFUL AKMAL', 'DIT5B', '050101000010'),
(4, 2, '34DIT24F1003', 'NUR ALYA FARZANA BINTI MOHAMAD FAEZAL', 'DIT5B', '060101000011'),
(4, 3, '34DIT24F1008', 'NUR FASYA DALILAH BINTI MOHD FAZELI', 'DIT5A', '050101000012'),
(5, 1, '34DIT34F1028', 'ADAM HARIS BIN AMINUDIN', 'DIT5A', '060101000013'),
(5, 2, '34DIT24F1018', 'MUHAMMAD MUAZ FIQRI BIN ISRAMAZA', 'DIT5A', '050101000014'),
(5, 3, '34DIT24F1040', 'TENGKU MUHAMMAD ADIB BIN TENGKU ROSLAN', 'DIT5A', '060101000015'),
(6, 1, '34DIT24F1042', 'MUHAMMAD AFIQ AZFAR BIN RAMLI', 'DIT5A', '050101000016'),
(6, 2, '34DIT24F1019', 'MUHAMMAD HILMI AQIL BIN ZULKIFLI', 'DIT5B', '060101000017'),
(6, 3, '34DIT24F1004', 'MUHAMMAD FAYYAD AQEL BIN MOHD FAIZAL', 'DIT5A', '050101000018'),
(7, 1, '34DIT24F1048', 'MUHAMMAD FIRDAUS BIN KAMRI', 'DIT5A', '060101000019'),
(7, 2, '34DIT24F1026', 'MUHAMMAD ARIF ARMAN BIN ALIMIN', 'DIT5A', '050101000020'),
(7, 3, '34DIT24F1006', 'IEZZWAN ZAFRAN BIN AHMAD ZAILANI', 'DIT5A', '060101000021'),
(8, 1, '34DIT24F1044', 'NURUL ISMAH SYAHIRAH BINTI MOHAMMAD YUSOF', 'DIT5A', '050101000022'),
(8, 2, '34DIT24F1037', 'NUR ZARA ALYA BINTI ZAFRI AZRAN', 'DIT5B', '060101000023'),
(8, 3, '34DIT24F1032', 'NUR IZZAH ATHIRAH BINTI ABDULLAH', 'DIT5A', '050101000024'),
(9, 1, '34DIT24F1056', 'AHMAD MUBASSYIR BIN AHMAD MUKRI', 'DIT5A', '060101000025'),
(9, 2, '34DIT24F1015', 'AHMAD HAZIQ HAKIMI BIN EZANI', 'DIT5B', '050101000026'),
(9, 3, '34DIT24F1057', 'MUHAMAD NUR HAKIM BIN JOHARI', 'DIT5B', '060101000027'),
(10, 1, '34DIT24F1016', 'NIK AMEERUL IKHWAN BIN NIK MOHD FAIZAL', 'DIT5A', '050101000028'),
(10, 2, '34DIT24F1055', 'MUHAMMAD SYAMSUL SAFWAN BIN ABDULLAH', 'DIT5B', '060101000029'),
(10, 3, '34DIT24F1043', 'MUHAMMAD RAFFIUDEEN BIN MOHD ROZAMI', 'DIT5B', '050101000030'),
(11, 1, '34DIT24D1038', 'DARSSHAN A/L THACHINAMOORTHY', 'DIT5A', '060101000031'),
(11, 2, '34DIT24D1035', 'DHARANIESSWARAN A/L GOPIRAJ', 'DIT5B', '050101000032'),
(11, 3, '34DIT24D1054', 'SHARVESHAN', 'DIT5A', '060101000033'),
(12, 1, '34DIT24F1024', 'MUHAMMAD AMIR BIN MOHD EZANI', 'DIT5A', '050101000034'),
(12, 2, '34DIT24F1062', 'WAN MAISARAH BINTI WAN MOHD ZAKI', 'DIT5A', '060101000035'),
(12, 3, '34DIT24F1001', 'NUR SHASSUHADAATIRAH BINTI MOHAMED AZMI', 'DIT5B', '050101000036'),
(13, 1, '34DIT24F1005', 'MUHAMMAD ILYAS UMAR BIN ZAMRI', 'DIT5B', '060101000037'),
(13, 2, '34DIT24F1007', 'AFIQ ARIFFIN BIN AMIR', 'DIT5B', '050101000038'),
(13, 3, '34DIT24F1009', 'MUHAMMAD NAZRIL IMMAN BIN ZAID', 'DIT5B', '060101000039'),
(14, 1, '34DIT24F1011', 'HAFIZUL IRFAN BIN AHMAD HILMI', 'DIT5B', '050101000040'),
(14, 2, '34DIT24F1063', 'TUAN NUR AWATIF BINTI ABDULLAH SANUSI', 'DIT5B', '060101000041'),
(14, 3, '34DIT24F1022', 'NUR SYAHIRAH BINTI MOHAMAD SAIDI', 'DIT5A', '050101000042'),
(15, 1, '34DIT24F1031', 'MUHAMMAD DANIAL ISKANDAR BIN JUSROFAISAL', 'DIT5B', '060101000043'),
(15, 2, '34DIT24F1025', 'MUHAMMAD FAIKAR HADIF BIN AZMAN', 'DIT5B', '050101000044'),
(15, 3, '34DIT24F1053', 'MUHD ADAM MUAZHAM BIN MOHD FARID', 'DIT5B', '060101000045'),
(16, 1, '34DIT24F1052', 'NURUL HIDAYAH IZZATI BINTI RAMLI', 'DIT5A', '050101000046'),
(16, 2, '34DIT24F1045', 'NUR HAZIQAH HAZIRAH BINTI MOHD REDZUAN', 'DIT5B', '060101000047'),
(17, 1, '34DIT24F1049', 'MUHAMMAD IZZAT HANIS BIN AHMAD', 'DIT5B', '050101000048'),
(17, 2, '34DIT24F1029', 'AMIRUL ASYRAF BIN AHMAD NASHARUDIN', 'DIT5B', '060101000049'),
(17, 3, '34DIT24F1027', 'MUHAMMAD AIDIL AFIQ BIN IDIL ISKANDAR', 'DIT5B', '050101000050'),
(18, 1, '34DIT24F1058', 'MUHAMMAD HARRIS NAZHAN BIN MOHAMAD', 'DIT5A', '060101000051'),
(18, 2, '34DIT24F1064', 'MUHAMAD ADAM THAQIF BIN MOHD SAIDI', 'DIT5A', '050101000052'),
(18, 3, '34DIT24F1033', 'NIK MUHAMMAD HARIS HAZIM BIN NIK MOHD AFANDI', 'DIT5B', '060101000053');

DROP TEMPORARY TABLE IF EXISTS import_fyp_projects;
CREATE TEMPORARY TABLE import_fyp_projects (
    group_no TINYINT NOT NULL PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    category VARCHAR(50) NOT NULL,
    description TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO import_fyp_projects (group_no, title, category, description) VALUES
(1, 'UIDM HUB SYSTEM', 'Information system', 'UIDM Hub system.'),
(2, 'Sistem Pengurusan ALK & Mesyuarat AGM', 'Information system', 'ALK and AGM meeting management system.'),
(3, 'SISTEM PENGURUSAN KOOP', 'Information system', 'Cooperative management system.'),
(4, 'Sistem I-JPP CENTER', 'Information system', 'I-JPP Center system.'),
(5, 'e-learning', 'Web application', 'E-learning system.'),
(6, 'Sistem Kelab Staf Polibest', 'Information system', 'PoliBesut Staff Club management system.'),
(7, 'Sistem I - JRKV', 'Information system', 'I-JRKV system.'),
(8, 'Sistem FYP', 'Information system', 'Final year project management system.'),
(9, 'Sistem Admin JTMK', 'Information system', 'JTMK administration system.'),
(10, 'Sistem E-Parcel Polibesut', 'Software application', 'PoliBesut e-Parcel management system.'),
(11, 'CSPS COLLAB', 'Information system', 'CSPS collaboration system.'),
(12, 'sistem management E EXAM', 'Web application', 'Electronic examination management system.'),
(13, 'JRKV Machine System', 'Hardware design', 'JRKV machine system.'),
(14, 'Sistem MyHEP_PoliBesut', 'Information system', 'MyHEP PoliBesut system.'),
(15, 'E-UJK', 'Information system', 'E-UJK system.'),
(16, 'Sportary Facility Booking System', 'Software application', 'Sports facility booking system.'),
(17, 'PoliSpace (Sistem Penempahan Dewan)', 'Web application', 'PoliSpace hall booking system.'),
(18, 'e-Formula Batik', 'Information system', 'e-Formula Batik system.');

DROP TEMPORARY TABLE IF EXISTS import_fyp_supervisors;
CREATE TEMPORARY TABLE import_fyp_supervisors (
  supervisor_no TINYINT NOT NULL PRIMARY KEY,
  full_name VARCHAR(150) NOT NULL,
  placeholder_ic CHAR(12) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO import_fyp_supervisors (supervisor_no, full_name, placeholder_ic) VALUES
(1, 'HARTATI BINTI MASKUR', '790101000001'),
(2, 'ENI ARYANTI BINTI YUSOFF', '800101000002'),
(3, 'ROSHILA BINTI ABDUL MUTALIB', '810101000003'),
(4, 'ABDUL HAKIM BIN ABDUL AZIZ', '790101000004'),
(5, 'QUTUBUDDIN BUZURGOON BIN HASSAN', '800101000005'),
(6, 'FIRDAUS BIN HASSAN', '810101000006'),
(7, 'NORAZLINA BINTI ABDULLAH', '790101000007'),
(8, 'ELISNORAZMALIZA BINTI AB HAMID', '800101000008'),
(9, 'AZRIND BINTI OTHMAN', '810101000009'),
(10, 'NORAKMAR BINTI MOHD NADZARI', '790101000010');

DROP TEMPORARY TABLE IF EXISTS import_fyp_project_supervisors;
CREATE TEMPORARY TABLE import_fyp_project_supervisors (
  group_no TINYINT NOT NULL PRIMARY KEY,
  supervisor_no TINYINT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO import_fyp_project_supervisors (group_no, supervisor_no) VALUES
(1, 1), (2, 2), (3, 3), (4, 4), (5, 1), (6, 5),
(7, 6), (8, 4), (9, 7), (10, 6), (11, 8), (12, 9),
(13, 10), (14, 7), (15, 10), (16, 8), (17, 9), (18, 2);

-- The source import numbers differ from the PDF group order. Keep the source
-- keys for member/supervisor joins, then assign the PDF number to projects.
DROP TEMPORARY TABLE IF EXISTS import_pdf_group_numbers;
CREATE TEMPORARY TABLE import_pdf_group_numbers (
    import_group_no TINYINT NOT NULL PRIMARY KEY,
    pdf_group_no TINYINT NOT NULL UNIQUE
) ENGINE=InnoDB;
INSERT INTO import_pdf_group_numbers (import_group_no, pdf_group_no) VALUES
(1, 18), (2, 16), (3, 17), (4, 6), (5, 8), (6, 11),
(7, 7), (8, 5), (9, 14), (10, 15), (11, 1), (12, 4),
(13, 9), (14, 10), (15, 2), (16, 13), (17, 12), (18, 3);

-- Reuse the two demonstration projects from seed_fixed_jtmk_users_projects.sql.
UPDATE projects p
JOIN import_fyp_students leader ON leader.member_no = 1
JOIN users u ON u.matric_no = leader.matric_no
JOIN import_fyp_projects ip ON ip.group_no = leader.group_no
SET p.title = ip.title,
    p.department = 'JTMK',
    p.program_name = 'JTMK - Information Technology',
    p.course_code = 'DFT50114',
    p.category = ip.category,
    p.session = 'Session 1 2026/2027',
    p.description = ip.description
WHERE p.student_id = u.id
  AND p.title IN ('JTMK Smart Information System', 'JTMK Web Application Project');

-- Migrate earlier synthetic IC values to the alternating 06/05 placeholders.
UPDATE users u
JOIN import_fyp_students s ON s.matric_no = u.matric_no
SET u.username = s.placeholder_ic,
    u.ic_number = s.placeholder_ic
WHERE u.role = 'Student'
  AND (
      u.ic_number BETWEEN '990000000001' AND '990000000053'
      OR u.ic_number BETWEEN '060500000001' AND '060500000053'
  );

-- The existing fixed seed password is DFT50114@2026; replace it before real use.

INSERT INTO users
    (username, ic_number, matric_no, full_name, email, password, role, department, program_name, course_code, track, class_name, phone_no)
SELECT s.placeholder_ic,
       s.placeholder_ic,
       s.matric_no,
       s.full_name,
       CONCAT(LOWER(s.matric_no), '@jtmk.local'),
       '$2y$10$hLraNgWdEWiz/dO1vS1zZ.UvTTJGUKEoIPpGrxkWAqNSjASjpr0hK',
       'Student',
       'JTMK',
       'JTMK - Information Technology',
       'DFT50114',
       'DIT',
       s.class_name,
       NULL
FROM import_fyp_students s
WHERE NOT EXISTS (SELECT 1 FROM users u WHERE u.matric_no = s.matric_no)
  AND NOT EXISTS (SELECT 1 FROM users u WHERE u.ic_number = s.placeholder_ic);

-- Reuse existing supervisor accounts by name; create only missing supervisor users.
UPDATE users u
JOIN import_fyp_supervisors s ON UPPER(TRIM(u.full_name)) = s.full_name
SET u.username = s.placeholder_ic,
    u.ic_number = s.placeholder_ic
WHERE u.role = 'Supervisor'
  AND LEFT(u.ic_number, 2) NOT IN ('79', '80', '81');

INSERT INTO users
    (username, ic_number, matric_no, full_name, email, password, role, department, program_name, course_code, track, class_name, phone_no)
SELECT s.placeholder_ic,
       s.placeholder_ic,
       NULL,
       s.full_name,
       CONCAT('sv_', s.placeholder_ic, '@jtmk.local'),
       '$2y$10$hLraNgWdEWiz/dO1vS1zZ.UvTTJGUKEoIPpGrxkWAqNSjASjpr0hK',
       'Supervisor',
       'JTMK',
       'JTMK - Information Technology',
       'DFT50114',
       NULL,
       NULL,
       NULL
FROM import_fyp_supervisors s
WHERE NOT EXISTS (
    SELECT 1 FROM users u
    WHERE u.role = 'Supervisor'
      AND UPPER(TRIM(u.full_name)) = s.full_name
);

-- Insert each listed project once, using its listed leader as creator and owner.
INSERT INTO projects
    (created_by, supervisor_id, student_id, title, department, program_name, course_code, category, session, group_password, description)
SELECT leader_user.id,
       (SELECT id FROM users WHERE role = 'Supervisor' AND department = 'JTMK' ORDER BY id LIMIT 1),
       leader_user.id,
       ip.title,
       'JTMK',
       'JTMK - Information Technology',
       'DFT50114',
       ip.category,
       'Session 1 2026/2027',
       NULL,
       ip.description
FROM import_fyp_projects ip
JOIN import_fyp_students leader ON leader.group_no = ip.group_no AND leader.member_no = 1
JOIN users leader_user ON leader_user.matric_no = leader.matric_no
WHERE NOT EXISTS (SELECT 1 FROM projects p WHERE p.title = ip.title);

-- Align an existing project with the listed leader when its title already exists.
UPDATE projects p
JOIN import_fyp_projects ip ON ip.title = p.title
JOIN import_pdf_group_numbers pdf ON pdf.import_group_no = ip.group_no
JOIN import_fyp_students leader ON leader.group_no = ip.group_no AND leader.member_no = 1
JOIN users leader_user ON leader_user.matric_no = leader.matric_no
SET p.student_id = leader_user.id,
    p.created_by = leader_user.id,
    p.project_group_no = pdf.pdf_group_no,
    p.department = 'JTMK',
    p.program_name = 'JTMK - Information Technology',
    p.course_code = 'DFT50114',
    p.category = ip.category,
    p.session = 'Session 1 2026/2027',
    p.description = ip.description;

-- Remove listed students from old groups and remove unlisted members from these projects.
DELETE pm
FROM project_members pm
JOIN import_fyp_students s ON 1 = 1
JOIN users u ON u.matric_no = s.matric_no
WHERE pm.student_id = u.id
  AND pm.project_id NOT IN (
      SELECT p.id
      FROM projects p
      JOIN import_fyp_projects ip ON ip.title = p.title
  );

DELETE pm
FROM project_members pm
JOIN projects p ON p.id = pm.project_id
JOIN import_fyp_projects ip ON ip.title = p.title
JOIN users member_user ON member_user.id = pm.student_id
WHERE NOT EXISTS (
    SELECT 1
    FROM import_fyp_students s
    JOIN users expected_user ON expected_user.matric_no = s.matric_no
    WHERE s.group_no = ip.group_no
      AND expected_user.id = pm.student_id
);

INSERT INTO project_members (project_id, student_id, role, member_order)
SELECT p.id, u.id, IF(s.member_no = 1, 'Leader', 'Member'), s.member_no
FROM import_fyp_students s
JOIN users u ON u.matric_no = s.matric_no
JOIN import_fyp_projects ip ON ip.group_no = s.group_no
JOIN projects p ON p.title = ip.title
WHERE NOT EXISTS (
    SELECT 1 FROM project_members pm
    WHERE pm.project_id = p.id AND pm.student_id = u.id
);

UPDATE project_members pm
JOIN projects p ON p.id = pm.project_id
JOIN import_fyp_projects ip ON ip.title = p.title
JOIN import_fyp_students s ON s.group_no = ip.group_no
JOIN users u ON u.matric_no = s.matric_no AND u.id = pm.student_id
SET pm.role = IF(s.member_no = 1, 'Leader', 'Member'),
    pm.member_order = s.member_no;

UPDATE projects p
JOIN import_fyp_projects ip ON ip.title = p.title
JOIN import_fyp_project_supervisors ips ON ips.group_no = ip.group_no
JOIN import_fyp_supervisors source_sv ON source_sv.supervisor_no = ips.supervisor_no
JOIN users sv ON sv.role = 'Supervisor'
  AND UPPER(TRIM(sv.full_name)) = source_sv.full_name
SET p.supervisor_id = sv.id;

DELETE ss
FROM supervisor_students ss
JOIN import_fyp_students s ON 1 = 1
JOIN users student ON student.matric_no = s.matric_no
WHERE ss.student_id = student.id;

INSERT INTO supervisor_students (supervisor_id, student_id, session)
SELECT sv.id, student.id, 'Session 1 2026/2027'
FROM import_fyp_students s
JOIN users student ON student.matric_no = s.matric_no
JOIN import_fyp_project_supervisors ips ON ips.group_no = s.group_no
JOIN import_fyp_supervisors source_sv ON source_sv.supervisor_no = ips.supervisor_no
JOIN users sv ON sv.role = 'Supervisor'
  AND UPPER(TRIM(sv.full_name)) = source_sv.full_name;

DELETE ss
FROM supervisor_students ss
JOIN users stale_student ON stale_student.id = ss.student_id
JOIN users sv ON sv.id = ss.supervisor_id
JOIN import_fyp_supervisors source_sv
  ON UPPER(TRIM(sv.full_name)) = source_sv.full_name
WHERE stale_student.matric_no = '34DIT24F1060'
  AND NOT EXISTS (
    SELECT 1 FROM import_fyp_students s
    WHERE s.matric_no = stale_student.matric_no
  );

COMMIT;

SELECT pdf.pdf_group_no AS group_no, ip.title, COUNT(DISTINCT pm.student_id) AS imported_members
FROM import_fyp_projects ip
JOIN import_pdf_group_numbers pdf ON pdf.import_group_no = ip.group_no
JOIN projects p ON p.title = ip.title
LEFT JOIN project_members pm ON pm.project_id = p.id
GROUP BY pdf.pdf_group_no, ip.title
ORDER BY pdf.pdf_group_no;

SELECT pdf.pdf_group_no AS group_no, ip.title, sv.full_name AS supervisor_name, COUNT(DISTINCT student.id) AS assigned_students
FROM import_fyp_projects ip
JOIN import_pdf_group_numbers pdf ON pdf.import_group_no = ip.group_no
JOIN projects p ON p.title = ip.title
JOIN users sv ON sv.id = p.supervisor_id
LEFT JOIN supervisor_students ss ON ss.supervisor_id = sv.id
LEFT JOIN import_fyp_students s ON s.group_no = ip.group_no
LEFT JOIN users student ON student.matric_no = s.matric_no AND student.id = ss.student_id
GROUP BY pdf.pdf_group_no, ip.title, sv.full_name
ORDER BY pdf.pdf_group_no;
