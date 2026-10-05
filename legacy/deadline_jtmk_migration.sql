-- Align deadline reminders with the official DFT50114 upload categories.

UPDATE submission_deadlines SET title = 'A: Proposal Presentation', description = 'Submit the proposal presentation document for supervisor verification.' WHERE id = 1;
UPDATE submission_deadlines SET title = 'B: Project Demonstration 1', description = 'Submit the Demo 1 supporting document before the scheduled deadline.' WHERE id = 2;
UPDATE submission_deadlines SET title = 'C: Project Demonstration 2', description = 'Submit the Demo 2 supporting document before the scheduled deadline.' WHERE id = 3;
UPDATE submission_deadlines SET title = 'D: Project Demonstration 3', description = 'Submit the Demo 3 supporting document before the scheduled deadline.' WHERE id = 4;
UPDATE submission_deadlines SET title = 'E: Final Presentation - Poster', description = 'Submit the final presentation poster for project documentation.' WHERE id = 5;
UPDATE submission_deadlines SET title = 'F: Final Presentation', description = 'Submit the final presentation document for project completion.' WHERE id = 6;

INSERT INTO submission_deadlines (title, description, due_date)
SELECT 'Log Book', 'Submit the project log book for weekly supervisor verification.', NULL
WHERE NOT EXISTS (SELECT 1 FROM submission_deadlines WHERE title = 'Log Book');

INSERT INTO submission_deadlines (title, description, due_date)
SELECT 'Technical Report', 'Submit the final technical report for project documentation.', NULL
WHERE NOT EXISTS (SELECT 1 FROM submission_deadlines WHERE title = 'Technical Report');
