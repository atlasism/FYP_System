-- Reorder DFT50194 groups to match senaraipelajarprojek2_DFT50194.
-- Run after the DFT50194 projects and their leaders have been imported.
-- Group leaders' matric numbers are used so projects are matched independently
-- of their current group number or database ID.

START TRANSACTION;

UPDATE projects AS p
JOIN users AS leader ON leader.id = p.student_id
SET p.project_group_no = CASE leader.matric_no
    WHEN '34DIT24F1024' THEN 1
    WHEN '34DIT24F1049' THEN 2
    WHEN '34DIT24F1016' THEN 3
    WHEN '34DIT24F1011' THEN 4
    WHEN '34DIT24F1042' THEN 5
    WHEN '34DIT24F1014' THEN 6
    WHEN '34DIT24F1047' THEN 7
    WHEN '34DIT24F1044' THEN 8
    WHEN '34DIT24F1038' THEN 9
    WHEN '34DIT24F1010' THEN 10
    WHEN '34DIT24F1064' THEN 11
    WHEN '34DIT24F1031' THEN 12
    WHEN '34DIT24F1006' THEN 13
    WHEN '34DIT24F1028' THEN 14
    WHEN '34DIT24F1007' THEN 15
    WHEN '34DIT24F1052' THEN 16
    WHEN '34DIT24F1056' THEN 17
    WHEN '34DIT24F1051' THEN 18
END
WHERE p.course_code = 'DFT50194'
  AND p.department = 'JTMK'
  AND p.session = 'Session 1 2026/2027'
  AND leader.matric_no IN (
      '34DIT24F1024', '34DIT24F1049', '34DIT24F1016',
      '34DIT24F1011', '34DIT24F1042', '34DIT24F1014',
      '34DIT24F1047', '34DIT24F1044', '34DIT24F1038',
      '34DIT24F1010', '34DIT24F1064', '34DIT24F1031',
      '34DIT24F1006', '34DIT24F1028', '34DIT24F1007',
      '34DIT24F1052', '34DIT24F1056', '34DIT24F1051'
  );

COMMIT;
