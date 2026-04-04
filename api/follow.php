<?php
// api/follow.php - Follow/Unfollow API endpoint
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
    $target_id = (int)($_POST['user_id'] ?? 0);
    $tag_id = (int)($_POST['tag_id'] ?? 0);
    
    if ($tag_id > 0) {
        // Tag follow
        if (is_following_tag(current_user_id(), $tag_id)) {
            db_exec("DELETE FROM tag_follows WHERE user_id = ? AND tag_id = ?", [current_user_id(), $tag_id]);
            echo json_encode(['success' => true, 'following' => false]);
        } else {
            db_insert("INSERT INTO tag_follows (user_id, tag_id, created_at) VALUES (?, ?, NOW())", [current_user_id(), $tag_id]);
            echo json_encode(['success' => true, 'following' => true]);
        }
    } elseif ($target_id > 0) {
        // Can't follow yourself
        if ($target_id === current_user_id()) {
            http_response_code(400);
            echo json_encode(['error' => 'Cannot follow yourself']);
            exit;
        }
        
        if ($action === 'unfollow') {
            db_exec("DELETE FROM user_follows WHERE follower_id = ? AND following_id = ?", [current_user_id(), $target_id]);
            echo json_encode(['success' => true, 'following' => false]);
        } else {
            // Toggle or follow
            if (!is_following(current_user_id(), $target_id)) {
                db_insert("INSERT INTO user_follows (follower_id, following_id, created_at) VALUES (?, ?, NOW())", [current_user_id(), $target_id]);
                // Notify
                $target = db_row("SELECT username FROM users WHERE id = ?", [$target_id]);
                send_notification($target_id, 'new_follower', ['follower_id' => current_user_id(), 'follower_name' => current_user()['username']]);
            }
            echo json_encode(['success' => true, 'following' => true]);
        }
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid request']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}

