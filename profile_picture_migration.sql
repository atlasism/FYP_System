-- Add profile picture storage for all user roles.
-- Run once against fyp_inventory_db.

ALTER TABLE users
    ADD COLUMN profile_picture VARCHAR(255) NULL AFTER email;
