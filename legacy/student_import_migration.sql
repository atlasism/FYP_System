-- Add academic session storage for imported student accounts.
-- Run once against fyp_inventory_db before importing students.

ALTER TABLE users
    ADD COLUMN academic_session VARCHAR(50) NULL AFTER phone_no;