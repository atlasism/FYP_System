-- Match the PDF "00 DFT50194 Senarai Nama Projek & Pelajar.pdf" group order.
-- These imported application projects use course code DFT50114. Their team
-- membership matches the PDF, although some project titles and matric prefixes differ.
-- Run this only for Session 1 2026/2027 after importing the 18 projects.

START TRANSACTION;

UPDATE projects AS p
JOIN project_members AS pm ON pm.project_id = p.id
JOIN users AS u ON u.id = pm.student_id
SET p.project_group_no = CASE u.matric_no
    WHEN '34DIT24F1038' THEN 1
    WHEN '34DIT24D1038' THEN 1
    WHEN '34DIT24F1031' THEN 2
    WHEN '34DIT24F1064' THEN 3
    WHEN '34DIT24F1024' THEN 4
    WHEN '34DIT24F1044' THEN 5
    WHEN '34DIT24F1047' THEN 6
    WHEN '34DIT24F1006' THEN 7
    WHEN '34DIT24F1018' THEN 8
    WHEN '34DIT24F1007' THEN 9
    WHEN '34DIT24F1011' THEN 10
    WHEN '34DIT24F1042' THEN 11
    WHEN '34DIT24F1049' THEN 12
    WHEN '34DIT24F1052' THEN 13
    WHEN '34DIT24F1056' THEN 14
    WHEN '34DIT24F1016' THEN 15
    WHEN '34DIT24F1010' THEN 16
    WHEN '34DIT24F1014' THEN 17
    WHEN '34DIT24F1017' THEN 18
END
WHERE p.department = 'JTMK'
  AND p.course_code = 'DFT50114'
  AND p.session = 'Session 1 2026/2027'
  AND u.matric_no IN (
      '34DIT24F1038', '34DIT24D1038', '34DIT24F1031',
      '34DIT24F1064', '34DIT24F1024', '34DIT24F1044',
      '34DIT24F1047', '34DIT24F1006', '34DIT24F1018',
      '34DIT24F1007', '34DIT24F1011', '34DIT24F1042',
      '34DIT24F1049', '34DIT24F1052', '34DIT24F1016',
      '34DIT24F1010', '34DIT24F1014', '34DIT24F1017'
  );

COMMIT;

SELECT project_group_no, title
FROM projects
WHERE department = 'JTMK'
  AND course_code = 'DFT50114'
  AND session = 'Session 1 2026/2027'
ORDER BY project_group_no;
