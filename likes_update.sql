-- ============================================================
-- PANTHERVERSE — Likes System Database Schema
-- Run this to enable the like feature
-- ============================================================

USE `pantherverse_db`;
SET FOREIGN_KEY_CHECKS = 0;

-- Likes table - stores all likes
CREATE TABLE IF NOT EXISTS `likes` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `liked_id` BIGINT UNSIGNED NOT NULL,
    `liked_type` VARCHAR(100) NOT NULL COMMENT 'forum_post, resource, project, announcement, question, answer',
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `likes_user_type_unique` (`user_id`, `liked_id`, `liked_type`),
    KEY `likes_user_id_foreign` (`user_id`),
    KEY `likes_liked_id_index` (`liked_id`),
    KEY `likes_liked_type_index` (`liked_type`),
    CONSTRAINT `likes_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add like_count columns to existing tables
ALTER TABLE `forum_posts` ADD COLUMN `like_count` INT NOT NULL DEFAULT 0 AFTER `reply_count`;
ALTER TABLE `resources` ADD COLUMN `like_count` INT NOT NULL DEFAULT 0 AFTER `download_count`;
ALTER TABLE `projects` ADD COLUMN `like_count` INT NOT NULL DEFAULT 0 AFTER `is_endorsed`;
ALTER TABLE `announcements` ADD COLUMN `like_count` INT NOT NULL DEFAULT 0 AFTER `priority`;
ALTER TABLE `questions` ADD COLUMN `like_count` INT NOT NULL DEFAULT 0 AFTER `accepted_answer_id`;
ALTER TABLE `answers` ADD COLUMN `like_count` INT NOT NULL DEFAULT 0 AFTER `vote_count`;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- DONE! Like system is now enabled:
--   - likes table created
--   - like_count columns added to:
--     * forum_posts
--     * resources
--     * projects
--     * announcements
--     * questions
--     * answers
-- ============================================================

