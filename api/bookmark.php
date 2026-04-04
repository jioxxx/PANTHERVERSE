<?php
// api/bookmark.php - Bookmark API endpoint
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
    
    $action = $_POST['action'] ?? 'toggle';
    $type = $_POST['type'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    
    $valid_types = ['question', 'resource', 'forum_post', 'project'];
    if (!in_array($type, $valid_types) || !$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid request']);
        exit;
    }
    
    if ($action === 'remove') {
        $result = db_exec("DELETE FROM bookmarks WHERE user_id = ? AND bookmarkable_id = ? AND bookmarkable_type = ?",
            [current_user_id(), $id, $type]);
        echo json_encode(['success' => true, 'bookmarked' => false]);
    } else {
        // Toggle
        $is_bookmarked = is_bookmarked(current_user_id(), $id, $type);
        
        if ($is_bookmarked) {
            db_exec("DELETE FROM bookmarks WHERE user_id = ? AND bookmarkable_id = ? AND bookmarkable_type = ?",
                [current_user_id(), $id, $type]);
        } else {
            db_insert("INSERT INTO bookmarks (user_id, bookmarkable_id, bookmarkable_type, created_at) VALUES (?, ?, ?, NOW())",
                [current_user_id(), $id, $type]);
        }
        
        echo json_encode(['success' => true, 'bookmarked' => !$is_bookmarked]);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}

