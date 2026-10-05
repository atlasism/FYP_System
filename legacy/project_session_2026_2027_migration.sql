-- Set every existing project to the current project session.
-- Student registration sessions in users.academic_session are unchanged.
UPDATE projects
SET session = 'Session 1 2026/2027';
