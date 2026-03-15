<?php
require_once __DIR__ . '/includes/db.php';
try {
    db_exec("ALTER TABLE `users` ADD COLUMN `cover_photo` VARCHAR(255) NULL DEFAULT NULL AFTER `profile_photo`;");
    echo "COLUMN_ADDED_SUCCESSFULLY";
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false || strpos($e->getMessage(), 'already exists') !== false) {
        echo "COLUMN_ALREADY_EXISTS";
    } else {
        echo "ERROR: " . $e->getMessage();
    }
}
