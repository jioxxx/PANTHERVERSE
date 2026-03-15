-- Migration: Add year_level to users table for student year tracking
-- Run: mysql -u root -p pantherverse_db < migrations/add_year_level_and_role.sql

ALTER TABLE users 
ADD COLUMN `year_level` TINYINT(1) UNSIGNED NULL DEFAULT NULL COMMENT '1-4 for students, NULL for instructors/admins',
ADD INDEX `users_year_level_idx` (`year_level`);

-- Set year_level for demo students based on bios/context
UPDATE users SET year_level = 3 WHERE username IN ('juandc', 'ana_reyes');  -- 3rd year implied
UPDATE users SET year_level = 2 WHERE username = 'markv';                  -- 2nd year
UPDATE users SET year_level = 3 WHERE username = 'lizag';                  -- 3rd year BSIT
UPDATE users SET year_level = 3 WHERE username = 'carlom';                 -- 3rd year BSCS

-- Verify
SELECT username, role, year_level, campus_id, program_id FROM users ORDER BY id;

