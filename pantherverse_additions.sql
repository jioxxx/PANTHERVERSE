-- ============================================================
-- PANTHERVERSE — Feature Additions SQL
-- Run this AFTER pantherverse_db.sql is already imported
-- ============================================================
-- New tables for:
--   1. Instructor Consultation Booking
--   2. Study Groups
--   3. Direct Messaging
--   4. Academic Calendar
--   5. Announcements (extended)
-- ============================================================

USE `pantherverse_db`;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- 1. INSTRUCTOR AVAILABILITY
--    Instructors set their available time slots
-- ============================================================
CREATE TABLE IF NOT EXISTS `instructor_availability` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`     BIGINT UNSIGNED NOT NULL,
  `day_of_week` TINYINT NOT NULL COMMENT '0=Sun,1=Mon,...,6=Sat',
  `start_time`  TIME NOT NULL,
  `end_time`    TIME NOT NULL,
  `location`    VARCHAR(200) NULL DEFAULT NULL COMMENT 'Room, online link, etc.',
  `subject`     VARCHAR(150) NULL DEFAULT NULL,
  `is_active`   TINYINT(1) NOT NULL DEFAULT 1,
  `created_at`  TIMESTAMP NULL DEFAULT NULL,
  `updated_at`  TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `availability_user_id_foreign` (`user_id`),
  CONSTRAINT `availability_user_id_foreign`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 2. CONSULTATION REQUESTS
--    Students book a consultation with an instructor
-- ============================================================
CREATE TABLE IF NOT EXISTS `consultations` (
  `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id`      BIGINT UNSIGNED NOT NULL,
  `instructor_id`   BIGINT UNSIGNED NOT NULL,
  `subject`         VARCHAR(200) NOT NULL,
  `message`         TEXT NOT NULL COMMENT 'Student question/context',
  `preferred_date`  DATE NOT NULL,
  `preferred_time`  TIME NOT NULL,
  `status`          ENUM('pending','approved','declined','completed','cancelled') NOT NULL DEFAULT 'pending',
  `instructor_note` TEXT NULL DEFAULT NULL COMMENT 'Instructor reply/decline reason',
  `question_id`     BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'Related Q&A question (optional)',
  `created_at`      TIMESTAMP NULL DEFAULT NULL,
  `updated_at`      TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `consultations_student_id_foreign`    (`student_id`),
  KEY `consultations_instructor_id_foreign` (`instructor_id`),
  KEY `consultations_question_id_foreign`   (`question_id`),
  CONSTRAINT `consultations_student_id_foreign`
    FOREIGN KEY (`student_id`)    REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `consultations_instructor_id_foreign`
    FOREIGN KEY (`instructor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `consultations_question_id_foreign`
    FOREIGN KEY (`question_id`)   REFERENCES `questions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 3. STUDY GROUPS
-- ============================================================
CREATE TABLE IF NOT EXISTS `study_groups` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `owner_id`    BIGINT UNSIGNED NOT NULL,
  `name`        VARCHAR(150) NOT NULL,
  `subject`     VARCHAR(150) NOT NULL,
  `description` TEXT NULL DEFAULT NULL,
  `is_private`  TINYINT(1) NOT NULL DEFAULT 0,
  `max_members` INT NOT NULL DEFAULT 20,
  `program_id`  BIGINT UNSIGNED NULL DEFAULT NULL,
  `campus_id`   BIGINT UNSIGNED NULL DEFAULT NULL,
  `created_at`  TIMESTAMP NULL DEFAULT NULL,
  `updated_at`  TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `study_groups_owner_id_foreign`   (`owner_id`),
  KEY `study_groups_program_id_foreign` (`program_id`),
  KEY `study_groups_campus_id_foreign`  (`campus_id`),
  CONSTRAINT `study_groups_owner_id_foreign`
    FOREIGN KEY (`owner_id`)   REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `study_groups_program_id_foreign`
    FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`) ON DELETE SET NULL,
  CONSTRAINT `study_groups_campus_id_foreign`
    FOREIGN KEY (`campus_id`)  REFERENCES `campuses` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Study group members
CREATE TABLE IF NOT EXISTS `study_group_members` (
  `user_id`       BIGINT UNSIGNED NOT NULL,
  `group_id`      BIGINT UNSIGNED NOT NULL,
  `role`          ENUM('member','moderator') NOT NULL DEFAULT 'member',
  `joined_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`, `group_id`),
  KEY `sgm_group_id_foreign` (`group_id`),
  CONSTRAINT `sgm_user_id_foreign`
    FOREIGN KEY (`user_id`)  REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sgm_group_id_foreign`
    FOREIGN KEY (`group_id`) REFERENCES `study_groups` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Study group posts / discussions
CREATE TABLE IF NOT EXISTS `study_group_posts` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `group_id`   BIGINT UNSIGNED NOT NULL,
  `user_id`    BIGINT UNSIGNED NOT NULL,
  `body`       TEXT NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sgp_group_id_foreign` (`group_id`),
  KEY `sgp_user_id_foreign`  (`user_id`),
  CONSTRAINT `sgp_group_id_foreign`
    FOREIGN KEY (`group_id`) REFERENCES `study_groups` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sgp_user_id_foreign`
    FOREIGN KEY (`user_id`)  REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 4. DIRECT MESSAGES
-- ============================================================
CREATE TABLE IF NOT EXISTS `messages` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `sender_id`   BIGINT UNSIGNED NOT NULL,
  `receiver_id` BIGINT UNSIGNED NOT NULL,
  `body`        TEXT NOT NULL,
  `is_read`     TINYINT(1) NOT NULL DEFAULT 0,
  `created_at`  TIMESTAMP NULL DEFAULT NULL,
  `updated_at`  TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `messages_sender_id_foreign`   (`sender_id`),
  KEY `messages_receiver_id_foreign` (`receiver_id`),
  KEY `messages_conversation_index`  (`sender_id`, `receiver_id`),
  CONSTRAINT `messages_sender_id_foreign`
    FOREIGN KEY (`sender_id`)   REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `messages_receiver_id_foreign`
    FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 5. ACADEMIC CALENDAR EVENTS
-- ============================================================
CREATE TABLE IF NOT EXISTS `calendar_events` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`     BIGINT UNSIGNED NOT NULL COMMENT 'Creator (admin/instructor)',
  `title`       VARCHAR(250) NOT NULL,
  `description` TEXT NULL DEFAULT NULL,
  `event_date`  DATE NOT NULL,
  `end_date`    DATE NULL DEFAULT NULL,
  `event_type`  ENUM('exam','deadline','holiday','event','class','other') NOT NULL DEFAULT 'event',
  `campus_id`   BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'NULL = all campuses',
  `program_id`  BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'NULL = all programs',
  `created_at`  TIMESTAMP NULL DEFAULT NULL,
  `updated_at`  TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `calendar_events_user_id_foreign`    (`user_id`),
  KEY `calendar_events_campus_id_foreign`  (`campus_id`),
  KEY `calendar_events_program_id_foreign` (`program_id`),
  KEY `calendar_events_date_index`         (`event_date`),
  CONSTRAINT `calendar_events_user_id_foreign`
    FOREIGN KEY (`user_id`)    REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `calendar_events_campus_id_foreign`
    FOREIGN KEY (`campus_id`)  REFERENCES `campuses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `calendar_events_program_id_foreign`
    FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- SEED DATA
-- ============================================================

-- Instructor availability (Prof. Santos — Mon/Wed/Fri)
INSERT INTO `instructor_availability` VALUES
(1, 2, 1, '09:00:00', '11:00:00', 'CS Lab 2, Main Campus', 'Data Structures & Algorithms', 1, NOW(), NOW()),
(2, 2, 3, '09:00:00', '11:00:00', 'CS Lab 2, Main Campus', 'Data Structures & Algorithms', 1, NOW(), NOW()),
(3, 2, 5, '13:00:00', '15:00:00', 'Google Meet (link via email)', 'Algorithm Design', 1, NOW(), NOW()),
(4, 2, 2, '14:00:00', '16:00:00', 'CS Lab 2, Main Campus', 'Object-Oriented Programming', 1, NOW(), NOW()),
-- Prof. Bautista — Tue/Thu
(5, 8, 2, '10:00:00', '12:00:00', 'IS Room 1, Dipolog Campus', 'Database Management', 1, NOW(), NOW()),
(6, 8, 4, '10:00:00', '12:00:00', 'IS Room 1, Dipolog Campus', 'Systems Analysis & Design', 1, NOW(), NOW()),
(7, 8, 4, '14:00:00', '16:00:00', 'Zoom (link via email)', 'Database Design', 1, NOW(), NOW());

-- Sample consultations
INSERT INTO `consultations` VALUES
(1, 3, 2, 'Data Structures & Algorithms',
 'Hi Prof. Santos! I\'ve been struggling with understanding Big O notation and how to properly calculate time complexity for nested loops. The public answers on the forum helped a bit but I still have questions about specific cases.',
 DATE_ADD(CURDATE(), INTERVAL 3 DAY), '09:00:00', 'approved',
 'Sure Juan! Come by CS Lab 2 on that day. Bring your notes and the specific problems you\'re unsure about. We\'ll work through them together.',
 4, NOW(), NOW()),

(2, 4, 8, 'Database Management',
 'Good day Prof. Bautista! I need help with our database normalization assignment. I understand 1NF and 2NF but I keep getting confused with 3NF and BCNF. I\'d like a one-on-one explanation.',
 DATE_ADD(CURDATE(), INTERVAL 5 DAY), '10:00:00', 'pending',
 NULL, 7, NOW(), NOW()),

(3, 5, 2, 'Algorithm Design',
 'Professor, I\'m preparing for the upcoming algorithms exam and would appreciate a consultation on graph traversal algorithms — specifically BFS vs DFS and when to use each one.',
 DATE_ADD(CURDATE(), INTERVAL 7 DAY), '13:00:00', 'pending',
 NULL, NULL, NOW(), NOW()),

(4, 6, 2, 'Object-Oriented Programming',
 'Hi Prof. I need guidance on design patterns for our capstone project. Specifically Builder and Observer patterns — I\'ve read about them but not sure how to apply them in Laravel.',
 DATE_SUB(CURDATE(), INTERVAL 5 DAY), '14:00:00', 'completed',
 'Great session Liza! Remember to implement the Observer pattern using Laravel Events and Listeners as we discussed.',
 NULL, NOW(), NOW());

-- Study groups
INSERT INTO `study_groups` VALUES
(1, 3, 'BSCS Data Structures Study Group', 'Data Structures & Algorithms',
 'A study group for BSCS students tackling Data Structures. We share notes, solve problems together, and prepare for exams. Open to all year levels.', 0, 25, 1, 1, NOW(), NOW()),

(2, 4, 'Database Ninjas', 'Database Management Systems',
 'For students who want to master SQL, database design, and normalization. We run weekly exercises and share practice problems.', 0, 20, 2, 2, NOW(), NOW()),

(3, 7, 'Capstone Dev Squad', 'Capstone Project',
 'A group for 3rd and 4th year students working on capstone projects. Share progress, ask for code reviews, and collaborate on ideas.', 0, 30, 1, 1, NOW(), NOW()),

(4, 5, 'Networking & Security Circle', 'Computer Networks',
 'Deep dive into networking protocols, subnetting, and cybersecurity fundamentals. Open to all BSIT and BSCS students.', 0, 20, 3, 3, NOW(), NOW());

-- Study group members
INSERT INTO `study_group_members` VALUES
(3, 1, 'moderator', NOW()),
(4, 1, 'member',    NOW()),
(6, 1, 'member',    NOW()),
(7, 1, 'member',    NOW()),
(4, 2, 'moderator', NOW()),
(3, 2, 'member',    NOW()),
(7, 3, 'moderator', NOW()),
(3, 3, 'member',    NOW()),
(6, 3, 'member',    NOW()),
(5, 4, 'moderator', NOW()),
(7, 4, 'member',    NOW());

-- Study group posts
INSERT INTO `study_group_posts` VALUES
(1, 1, 3, 'Welcome everyone! Let\'s use this group to share DSA resources and help each other prepare for exams. I\'ll be posting weekly practice problems every Sunday. 🎯', NOW(), NOW(), NULL),
(2, 1, 6, 'Hi everyone! Excited to be here. I just uploaded a linked list cheat sheet to the Resources section. Check it out!', NOW(), NOW(), NULL),
(3, 1, 7, 'Does anyone have good resources for understanding recursion? The textbook explanation isn\'t clicking for me.', NOW(), NOW(), NULL),
(4, 1, 3, 'Check out the question I posted on the Q&A section — Prof. Santos answered it with really good code examples for recursion!', NOW(), NOW(), NULL),
(5, 2, 4, 'Welcome to Database Ninjas! First exercise: normalize this table to 3NF — StudentID, StudentName, CourseID, CourseName, InstructorID, InstructorName. Post your answer below!', NOW(), NOW(), NULL),
(6, 3, 7, 'Our capstone meeting is this Saturday 2PM at the CS Lab. Please bring your system design diagrams for review. @juandc @lizag', NOW(), NOW(), NULL),
(7, 4, 5, 'Sharing a great YouTube playlist on subnetting that really helped me understand CIDR notation. Will post the link in the Resources section.', NOW(), NOW(), NULL);

-- Direct messages
INSERT INTO `messages` VALUES
(1,  3, 2, 'Good day Prof. Santos! Thank you for accepting my consultation request. Should I prepare anything specific before our meeting?', 1, NOW(), NOW()),
(2,  2, 3, 'Hi Juan! Yes, please bring your notes on time complexity and the specific code examples where you\'re unsure about the Big O. See you then!', 1, NOW(), NOW()),
(3,  4, 8, 'Hi Prof. Bautista! I submitted a consultation request. Please let me know if the schedule works for you.', 0, NOW(), NOW()),
(4,  7, 3, 'Hey Juan! Are you joining the capstone meeting Saturday? I think we need to finalize the ER diagram.', 1, NOW(), NOW()),
(5,  3, 7, 'Yes I\'ll be there! Can you share the current ER diagram draft so I can review it beforehand?', 0, NOW(), NOW()),
(6,  6, 3, 'Juan could you help me understand how to implement the Observer pattern? Prof. Santos mentioned it but I still don\'t fully get it.', 1, NOW(), NOW()),
(7,  3, 6, 'Sure Liza! Check out the Laravel Events documentation. Also I posted a question about design patterns in the Q&A you might find helpful.', 0, NOW(), NOW());

-- Academic calendar events
INSERT INTO `calendar_events` VALUES
(1,  1, 'Midterm Examinations Week', 'Midterm exams for all computing programs. Check your respective schedules posted on the department boards.', DATE_ADD(CURDATE(), INTERVAL 14 DAY), DATE_ADD(CURDATE(), INTERVAL 18 DAY), 'exam', NULL, NULL, NOW(), NOW()),
(2,  2, 'CS Capstone Proposal Deadline', 'Last day to submit capstone project proposals to your respective advisers. Submit both hard and soft copies.', DATE_ADD(CURDATE(), INTERVAL 7 DAY), NULL, 'deadline', 1, 1, NOW(), NOW()),
(3,  8, 'Database Management Quiz 2', 'Covers normalization (1NF–3NF), ER diagrams, and SQL JOIN operations. Open book — notes allowed.', DATE_ADD(CURDATE(), INTERVAL 5 DAY), NULL, 'exam', 2, 2, NOW(), NOW()),
(4,  1, 'JRMSU Foundation Day', 'University holiday. No classes on all campuses.', DATE_ADD(CURDATE(), INTERVAL 21 DAY), NULL, 'holiday', NULL, NULL, NOW(), NOW()),
(5,  2, 'Tech Talk: AI in Philippine Education', 'Guest lecture by industry professionals. Open to all computing students. Venue: AVR Main Campus.', DATE_ADD(CURDATE(), INTERVAL 10 DAY), NULL, 'event', 1, NULL, NOW(), NOW()),
(6,  8, 'Systems Analysis Project Defense', 'Final project defense for BSIS 3rd year students. Bring full documentation.', DATE_ADD(CURDATE(), INTERVAL 28 DAY), NULL, 'deadline', 2, 2, NOW(), NOW()),
(7,  1, 'Final Examination Period', 'Final examinations for all programs. Schedules to be posted one week prior.', DATE_ADD(CURDATE(), INTERVAL 45 DAY), DATE_ADD(CURDATE(), INTERVAL 50 DAY), 'exam', NULL, NULL, NOW(), NOW()),
(8,  2, 'Algorithm Design Problem Set Due', 'Submit your algorithm analysis problem sets via email to prof.santos@jrmsu.edu.ph', DATE_ADD(CURDATE(), INTERVAL 3 DAY), NULL, 'deadline', 1, 1, NOW(), NOW());

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- DONE! New tables added:
--   instructor_availability  — instructor schedules
--   consultations            — booking requests
--   study_groups             — group info
--   study_group_members      — membership
--   study_group_posts        — group discussions
--   messages                 — direct messages
--   calendar_events          — academic calendar
-- ============================================================
