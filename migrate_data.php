<?php
// migrate_data.php
// This script extracts data from local MySQL and converts it to Postgres-compatible SQL

$db_mysql = new PDO('mysql:host=127.0.0.1;dbname=pantherverse_db', 'root', '');
$db_mysql->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$tables = [
    'campuses' => 'id',
    'programs' => 'id',
    'users' => 'id',
    'tags' => 'id',
    'questions' => 'id',
    'answers' => 'id',
    'forum_categories' => 'id',
    'forum_posts' => 'id',
    'instructor_availability' => 'id',
    'consultations' => 'id',
    'study_groups' => 'id',
    'study_group_members' => ['user_id', 'group_id'],
    'study_group_posts' => 'id',
    'messages' => 'id',
    'resources' => 'id',
    'announcements' => 'id',
    'projects' => 'id',
    'calendar_events' => 'id',
    'likes' => 'id',
    'bookmarks' => 'id',
    'votes' => 'id',
    'comments' => 'id',
    'user_follows' => 'id',
    'tag_follows' => 'id',
    'notifications' => 'id',
    'notification_preferences' => 'id',
    'user_preferences' => 'id',
    'activity_logs' => 'id',
    'reputation_logs' => 'id',
    'tag_wikis' => 'id',
    'content_flags' => 'id',
    'suggestions' => 'id',
    'suggestion_votes' => ['user_id', 'suggestion_id'],
    'question_tag' => ['question_id', 'tag_id'],
    'resource_tag' => ['resource_id', 'tag_id'],
];

echo "-- DATA MIGRATION FROM MYSQL TO POSTGRES\n";
echo "SET FOREIGN_KEY_CHECKS = 0; -- Only if you run this in a context that allows it, but in Postgres we'll just insert\n\n";

foreach ($tables as $table => $pkey) {
    try {
        $stmt = $db_mysql->query("SELECT * FROM `$table` ");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (empty($rows)) continue;

        echo "-- TABLE: $table\n";
        
        $columns = array_keys($rows[0]);
        $column_list = implode(', ', $columns);
        
        foreach ($rows as $row) {
            $values = [];
            foreach ($row as $col => $val) {
                if ($val === null) {
                    $values[] = "NULL";
                } elseif (is_numeric($val)) {
                    $values[] = $val;
                } else {
                    // Convert Boolean/TinyInt
                    if ($val === '1' || $val === '0') {
                        // Check if column is likely a boolean
                        if (str_starts_with($col, 'is_') || $col === 'is_active' || $col === 'is_solved' || $col === 'is_pinned' || $col === 'is_locked' || $col === 'is_private' || $col === 'is_read') {
                            $values[] = ($val === '1' ? 'TRUE' : 'FALSE');
                            continue;
                        }
                    }
                    $values[] = $db_mysql->quote($val);
                }
            }
            
            $val_list = implode(', ', $values);
            
            $conflict_clause = "";
            if (is_array($pkey)) {
                $conflict_clause = "(" . implode(', ', $pkey) . ")";
            } else {
                $conflict_clause = "($pkey)";
            }
            
            echo "INSERT INTO $table ($column_list) VALUES ($val_list) ON CONFLICT $conflict_clause DO NOTHING;\n";
        }
        
        // Update serial for Postgres
        if (!is_array($pkey)) {
            echo "SELECT setval('{$table}_{$pkey}_seq', (SELECT MAX($pkey) FROM $table));\n";
        }
        echo "\n";
    } catch (Exception $e) {
        echo "-- Error migrating $table: " . $e->getMessage() . "\n\n";
    }
}
