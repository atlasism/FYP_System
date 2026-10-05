-- Reorder current DFT50114 JTMK panel groups to match the supplied student list.
-- The installed panel data is DFT50114 for Session 1 2026/2027.
-- Anchor students are unique to their project, so rerunning this is safe.
-- Imported matric numbers for groups 9 and 18 have nonstandard prefixes.

START TRANSACTION;

UPDATE projects AS p
JOIN project_members AS pm ON pm.project_id = p.id
JOIN users AS u ON u.id = pm.student_id
SET p.project_group_no = CASE u.matric_no
    WHEN '34DIT24F1024' THEN 1
    WHEN '34DIT24F1049' THEN 2
    WHEN '34DIT24F1016' THEN 3
    WHEN '34DIT24F1011' THEN 4
    WHEN '34DIT24F1042' THEN 5
    WHEN '34DIT24F1014' THEN 6
    WHEN '34DIT24F1047' THEN 7
    WHEN '34DIT24F1044' THEN 8
    WHEN '34DIT24D1035' THEN 9
    WHEN '34DIT24F1010' THEN 10
    WHEN '34DIT24F1064' THEN 11
    WHEN '34DIT24F1031' THEN 12
    WHEN '34DIT24F1006' THEN 13
    WHEN '34DIT24F1018' THEN 14
    WHEN '34DIT24F1007' THEN 15
    WHEN '34DIT24F1052' THEN 16
    WHEN '34DIT24F1056' THEN 17
    WHEN '34DIT34F1051' THEN 18
END
WHERE p.department = 'JTMK'
  AND p.course_code = 'DFT50114'
  AND p.session = 'Session 1 2026/2027'
  AND u.matric_no IN (
      '34DIT24F1024', '34DIT24F1049', '34DIT24F1016',
      '34DIT24F1011', '34DIT24F1042', '34DIT24F1014',
      '34DIT24F1047', '34DIT24F1044', '34DIT24D1035',
      '34DIT24F1010', '34DIT24F1064', '34DIT24F1031',
      '34DIT24F1006', '34DIT24F1018', '34DIT24F1007',
      '34DIT24F1052', '34DIT24F1056', '34DIT34F1051'
  );

COMMIT;
