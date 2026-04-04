<?php
// api/like.php - Like/unlike content (forum posts, resources, projects, announcements, questions, answers)
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';


header('Content-Type: application/json');

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'Please login first']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    
    $type = $_POST['type'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    
    // Valid types - now includes question and answer
    $valid_types = ['forum_post', 'resource', 'project', 'announcement', 'question', 'answer'];
    if (!in_array($type, $valid_types) || $id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid request']);
        exit;
    }
    
    // Table and column mapping
    $table_map = [
        'forum_post' => 'forum_posts',
        'resource' => 'resources',
        'project' => 'projects',
        'announcement' => 'announcements',
        'question' => 'questions',
        'answer' => 'answers'
    ];
    
    $table = $table_map[$type];
    
    try {
        // Check if already liked
        $existing = db_row("SELECT id FROM likes WHERE user_id = ? AND liked_type = ? AND liked_id = ?",
            [current_user_id(), $type, $id]);
        
        if ($existing) {
            // Unlike
            db_exec("DELETE FROM likes WHERE id = ?", [$existing['id']]);
            
            // Try to update like_count if column exists
            try {
                db_exec("UPDATE $table SET like_count = like_count - 1 WHERE id = ?", [$id]);
            } catch (Exception $e) {
                // Column doesn't exist, ignore
            }
            $liked = false;
        } else {
            // Like
            db_insert("INSERT INTO likes (user_id, liked_type, liked_id, created_at) VALUES (?, ?, ?, NOW())",
                [current_user_id(), $type, $id]);
            
            // Try to update like_count if column exists
            try {
                db_exec("UPDATE $table SET like_count = like_count + 1 WHERE id = ?", [$id]);
            } catch (Exception $e) {
                // Column doesn't exist, ignore
            }
            $liked = true;
            
            // Notify content owner (only if likes table exists)
            try {
                $content = db_row("SELECT user_id FROM $table WHERE id = ?", [$id]);
                if ($content && $content['user_id'] != current_user_id()) {
                    send_notification($content['user_id'], 'content_liked', [
                        'liker_id' => current_user_id(),
                        'liker_name' => current_user()['username'],
                        'content_type' => $type,
                        'content_id' => $id
                    ]);
                }
            } catch (Exception $e) {
                // Table issue, ignore notification
            }
        }
        
        // Try to get updated count
        try {
            $new_count = db_count("SELECT like_count FROM $table WHERE id = ?", [$id]);
        } catch (Exception $e) {
            $new_count = 0;
        }
        
        echo json_encode(['success' => true, 'liked' => $liked, 'like_count' => $new_count]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
    
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}

