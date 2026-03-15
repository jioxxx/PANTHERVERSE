<?php
// setup-likes.php - Run this once to set up the likes system
// Access this file in your browser to set up the likes database tables

require_once 'includes/db.php';

$db = db(); // Get PDO connection via the db() function

$messages = [];

try {
    // Create likes table
    $db->exec("
        CREATE TABLE IF NOT EXISTS `likes` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `user_id` BIGINT UNSIGNED NOT NULL,
            `liked_id` BIGINT UNSIGNED NOT NULL,
            `liked_type` VARCHAR(100) NOT NULL,
            `created_at` TIMESTAMP NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `likes_user_type_unique` (`user_id`, `liked_id`, `liked_type`),
            KEY `likes_user_id_foreign` (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    $messages[] = "✅ Created 'likes' table";
} catch (Exception $e) {
    $messages[] = "❌ Error creating likes table: " . $e->getMessage();
}

$tables = [
    'forum_posts' => 'like_count',
    'resources' => 'like_count', 
    'projects' => 'like_count',
    'announcements' => 'like_count',
    'questions' => 'like_count',
    'answers' => 'like_count'
];

foreach ($tables as $table => $column) {
    try {
        // Check if column exists
        $stmt = $db->prepare("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
        $stmt->execute([$table, $column]);
        if (!$stmt->fetch()) {
            // Add column based on table structure
            $add_sql = match($table) {
                'forum_posts' => "ALTER TABLE `$table` ADD COLUMN `like_count` INT NOT NULL DEFAULT 0 AFTER `reply_count`",
                'resources' => "ALTER TABLE `$table` ADD COLUMN `like_count` INT NOT NULL DEFAULT 0 AFTER `download_count`",
                'projects' => "ALTER TABLE `$table` ADD COLUMN `like_count` INT NOT NULL DEFAULT 0 AFTER `is_endorsed`",
                'announcements' => "ALTER TABLE `$table` ADD COLUMN `like_count` INT NOT NULL DEFAULT 0 AFTER `priority`",
                'questions' => "ALTER TABLE `$table` ADD COLUMN `like_count` INT NOT NULL DEFAULT 0 AFTER `accepted_answer_id`",
                'answers' => "ALTER TABLE `$table` ADD COLUMN `like_count` INT NOT NULL DEFAULT 0 AFTER `vote_count`",
                default => null
            };
            
            if ($add_sql) {
                $db->exec($add_sql);
                $messages[] = "✅ Added `like_count` column to `$table`";
            }
        } else {
            $messages[] = "ℹ️ `like_count` already exists in `$table`";
        }
    } catch (Exception $e) {
        $messages[] = "❌ Error with `$table`: " . $e->getMessage();
    }
}

echo "<!DOCTYPE html><html><head><title>Likes Setup</title>";
echo "<style>
body { font-family: 'Nunito', sans-serif; background: #0e0720; color: #e8dff8; padding: 40px; }
h1 { color: #f4a623; }
.success { color: #10b981; }
.error { color: #ef4444; }
.info { color: #7c3aed; }
.btn { background: #f4a623; color: #1a0e38; padding: 10px 20px; text-decoration: none; border-radius: 8px; display: inline-block; margin-top: 20px; }
</style></head><body>";
echo "<h1>❤️ Likes System Setup</h1>";
foreach ($messages as $msg) {
    $class = strpos($msg, '✅') !== false ? 'success' : (strpos($msg, '❌') !== false ? 'error' : 'info');
    echo "<p class='$class'>$msg</p>";
}
echo "<p><strong>Setup complete!</strong></p>";
echo "<a href='index.php' class='btn'>Go to Homepage</a>";
echo "</body></html>";

