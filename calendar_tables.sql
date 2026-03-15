-- ============================================================
-- Calendar Events Table
-- ============================================================

CREATE TABLE IF NOT EXISTS `calendar_events` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `event_date` DATE NOT NULL,
  `event_type` ENUM('exam','deadline','holiday','event','class','other') NOT NULL DEFAULT 'event',
  `campus_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `program_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `calendar_events_user_id_foreign` (`user_id`),
  KEY `calendar_events_event_date_index` (`event_date`),
  KEY `calendar_events_campus_id_foreign` (`campus_id`),
  CONSTRAINT `calendar_events_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `calendar_events_campus_id_foreign` FOREIGN KEY (`campus_id`) REFERENCES `campuses` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample calendar events
INSERT INTO `calendar_events` (`user_id`, `title`, `description`, `event_date`, `event_type`, `campus_id`, `program_id`, `created_at`, `updated_at`) VALUES
(1, 'Midterm Exams', 'Midterm examination period for all courses', '2026-03-15', 'exam', NULL, NULL, NOW(), NOW()),
(1, 'Final Exams', 'Final examination period', '2026-04-20', 'exam', NULL, NULL, NOW(), NOW()),
(1, 'Spring Break', 'No classes', '2026-03-01', 'holiday', NULL, NULL, NOW(), NOW()),
(1, 'Project Deadline', 'Capstone project submission deadline', '2026-04-01', 'deadline', NULL, NULL, NOW(), NOW()),
(1, 'Tech Talk: AI in Education', 'Guest speaker from tech industry', '2026-03-10', 'event', NULL, NULL, NOW(), NOW()),
(1, 'Programming Contest', 'Annual coding competition', '2026-03-25', 'event', NULL, NULL, NOW(), NOW()),
(1, 'Registration Week', 'Course registration for next semester', '2026-05-01', 'event', NULL, NULL, NOW(), NOW());

