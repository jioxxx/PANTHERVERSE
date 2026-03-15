-- ============================================================
-- PANTHERVERSE — Enhanced Features Database Schema
-- Run this AFTER pantherverse_additions.sql
-- ============================================================

USE `pantherverse_db`;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- 1. BOOKMARKS - Save questions, resources, forum posts, projects
-- ============================================================
CREATE TABLE IF NOT EXISTS `bookmarks` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `bookmarkable_id` BIGINT UNSIGNED NOT NULL,
    `bookmarkable_type` VARCHAR(100) NOT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `bookmarks_user_type_unique` (`user_id`, `bookmarkable_id`, `bookmarkable_type`),
    KEY `bookmarks_user_id_foreign` (`user_id`),
    CONSTRAINT `bookmarks_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 2. USER FOLLOWS - Follow other users and tags
-- ============================================================
CREATE TABLE IF NOT EXISTS `user_follows` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `follower_id` BIGINT UNSIGNED NOT NULL,
    `following_id` BIGINT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `user_follows_unique` (`follower_id`, `following_id`),
    KEY `user_follows_follower_foreign` (`follower_id`),
    KEY `user_follows_following_foreign` (`following_id`),
    CONSTRAINT `user_follows_follower_foreign` FOREIGN KEY (`follower_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `user_follows_following_foreign` FOREIGN KEY (`following_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tag_follows` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `tag_id` BIGINT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `tag_follows_unique` (`user_id`, `tag_id`),
    KEY `tag_follows_user_foreign` (`user_id`),
    KEY `tag_follows_tag_foreign` (`tag_id`),
    CONSTRAINT `tag_follows_user_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `tag_follows_tag_foreign` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 3. NOTIFICATION PREFERENCES
-- ============================================================
CREATE TABLE IF NOT EXISTS `notification_preferences` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `notify_new_answer` TINYINT(1) NOT NULL DEFAULT 1,
    `notify_answer_accepted` TINYINT(1) NOT NULL DEFAULT 1,
    `notify_new_comment` TINYINT(1) NOT NULL DEFAULT 1,
    `notify_new_follower` TINYINT(1) NOT NULL DEFAULT 1,
    `notify_mention` TINYINT(1) NOT NULL DEFAULT 1,
    `notify_consultation` TINYINT(1) NOT NULL DEFAULT 1,
    `notify_study_group` TINYINT(1) NOT NULL DEFAULT 1,
    `notify_message` TINYINT(1) NOT NULL DEFAULT 1,
    `notify_announcement` TINYINT(1) NOT NULL DEFAULT 1,
    `email_new_answer` TINYINT(1) NOT NULL DEFAULT 0,
    `email_answer_accepted` TINYINT(1) NOT NULL DEFAULT 0,
    `email_new_follower` TINYINT(1) NOT NULL DEFAULT 0,
    `email_mention` TINYINT(1) NOT NULL DEFAULT 0,
    `email_consultation` TINYINT(1) NOT NULL DEFAULT 1,
    `email_announcement` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `notification_preferences_user_unique` (`user_id`),
    CONSTRAINT `notification_preferences_user_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 4. QUESTION DRAFTS - Save incomplete questions
-- ============================================================
CREATE TABLE IF NOT EXISTS `question_drafts` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `title` VARCHAR(350) NOT NULL,
    `body` LONGTEXT NOT NULL,
    `tags` VARCHAR(500) NULL DEFAULT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `question_drafts_user_id_foreign` (`user_id`),
    CONSTRAINT `question_drafts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 5. POLLS/SURVEYS - Create polls in forums
-- ============================================================
CREATE TABLE IF NOT EXISTS `polls` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `question_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `forum_post_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `title` VARCHAR(350) NOT NULL,
    `options` JSON NOT NULL,
    `is_multiple` TINYINT(1) NOT NULL DEFAULT 0,
    `expires_at` DATETIME NULL DEFAULT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `polls_user_id_foreign` (`user_id`),
    KEY `polls_question_id_foreign` (`question_id`),
    KEY `polls_forum_post_id_foreign` (`forum_post_id`),
    CONSTRAINT `polls_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `polls_question_id_foreign` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE,
    CONSTRAINT `polls_forum_post_id_foreign` FOREIGN KEY (`forum_post_id`) REFERENCES `forum_posts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `poll_votes` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `poll_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `option_index` TINYINT NOT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `poll_votes_unique` (`poll_id`, `user_id`, `option_index`),
    KEY `poll_votes_poll_foreign` (`poll_id`),
    KEY `poll_votes_user_foreign` (`user_id`),
    CONSTRAINT `poll_votes_poll_foreign` FOREIGN KEY (`poll_id`) REFERENCES `polls` (`id`) ON DELETE CASCADE,
    CONSTRAINT `poll_votes_user_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 6. API TOKENS - For REST API access
-- ============================================================
CREATE TABLE IF NOT EXISTS `api_tokens` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `token` VARCHAR(100) NOT NULL,
    `permissions` VARCHAR(500) NOT NULL DEFAULT 'read',
    `last_used_at` TIMESTAMP NULL DEFAULT NULL,
    `expires_at` DATETIME NULL DEFAULT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `api_tokens_token_unique` (`token`),
    KEY `api_tokens_user_id_foreign` (`user_id`),
    CONSTRAINT `api_tokens_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 7. ACTIVITY LOG - For analytics
-- ============================================================
CREATE TABLE IF NOT EXISTS `activity_logs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `action` VARCHAR(100) NOT NULL,
    `entity_type` VARCHAR(100) NULL DEFAULT NULL,
    `entity_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `metadata` JSON NULL DEFAULT NULL,
    `ip_address` VARCHAR(45) NULL DEFAULT NULL,
    `user_agent` TEXT NULL DEFAULT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `activity_logs_user_id_foreign` (`user_id`),
    KEY `activity_logs_created_at_index` (`created_at`),
    KEY `activity_logs_action_index` (`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 8. USER SESSIONS - Track login sessions
-- ============================================================
CREATE TABLE IF NOT EXISTS `user_sessions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `ip_address` VARCHAR(45) NULL DEFAULT NULL,
    `user_agent` VARCHAR(500) NULL DEFAULT NULL,
    `device_type` VARCHAR(50) NULL DEFAULT NULL,
    `location` VARCHAR(200) NULL DEFAULT NULL,
    `last_active_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `user_sessions_user_id_foreign` (`user_id`),
    CONSTRAINT `user_sessions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 9. TAG WIKI PAGES
-- ============================================================
CREATE TABLE IF NOT EXISTS `tag_wikis` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tag_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `content` LONGTEXT NOT NULL,
    `is_approved` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `tag_wikis_tag_unique` (`tag_id`),
    KEY `tag_wikis_tag_id_foreign` (`tag_id`),
    KEY `tag_wikis_user_id_foreign` (`user_id`),
    CONSTRAINT `tag_wikis_tag_id_foreign` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE,
    CONSTRAINT `tag_wikis_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 10. CONTENT FLAGS - Better moderation
-- ============================================================
CREATE TABLE IF NOT EXISTS `content_flags` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `flagger_id` BIGINT UNSIGNED NOT NULL,
    `content_type` VARCHAR(100) NOT NULL,
    `content_id` BIGINT UNSIGNED NOT NULL,
    `reason` ENUM('spam','harassment','inappropriate','misinformation','duplicate','other') NOT NULL,
    `description` TEXT NULL DEFAULT NULL,
    `status` ENUM('pending','reviewed','actioned','dismissed') NOT NULL DEFAULT 'pending',
    `reviewed_by` BIGINT UNSIGNED NULL DEFAULT NULL,
    `reviewed_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `content_flags_flagger_foreign` (`flagger_id`),
    KEY `content_flags_content_index` (`content_type`, `content_id`),
    CONSTRAINT `content_flags_flagger_foreign` FOREIGN KEY (`flagger_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 11. USER PREFERENCES - Dark mode, theme, etc
-- ============================================================
CREATE TABLE IF NOT EXISTS `user_preferences` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `theme` ENUM('dark','light','system') NOT NULL DEFAULT 'dark',
    `language` VARCHAR(10) NOT NULL DEFAULT 'en',
    `email_frequency` ENUM('instant','daily','weekly','never') NOT NULL DEFAULT 'daily',
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `user_preferences_user_unique` (`user_id`),
    CONSTRAINT `user_preferences_user_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- SEED DATA
-- ============================================================

-- Default notification preferences for existing users
INSERT INTO `notification_preferences` (`user_id`, `created_at`, `updated_at`)
SELECT id, NOW(), NOW() FROM users
ON DUPLICATE KEY UPDATE updated_at = NOW();

-- Default preferences for existing users
INSERT INTO `user_preferences` (`user_id`, `theme`, `language`, `created_at`, `updated_at`)
SELECT id, 'dark', 'en', NOW(), NOW() FROM users
ON DUPLICATE KEY UPDATE updated_at = NOW();

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- DONE! New tables added:
--   bookmarks              — Save content for later
--   user_follows           — Follow other users
--   tag_follows            — Follow tags
--   notification_preferences — Customize notifications
--   question_drafts        — Save incomplete questions
--   polls                  — Create polls
--   poll_votes             — Poll voting
--   api_tokens             — REST API access
--   activity_logs          — Analytics tracking
--   user_sessions          — Login session tracking
--   tag_wikis              — Tag wiki pages
--   content_flags          — Content moderation
--   user_preferences       — Dark mode, theme settings
-- ============================================================

