<?php
// vote.php — AJAX voting endpoint
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

if (!is_logged_in()) { echo json_encode(['error'=>'Login required.']); exit; }
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH'])) { echo json_encode(['error'=>'Invalid request.']); exit; }

$data  = json_decode(file_get_contents('php://input'), true);
$type  = $data['type'] ?? '';
$mid   = (int)($data['id'] ?? 0);
$value = (int)($data['value'] ?? 0);
$csrf  = $data['csrf'] ?? '';

if ($csrf !== csrf_token() || !in_array($value,[1,-1]) || !in_array($type,['question','answer'])) {
    echo json_encode(['error'=>'Invalid request.']); exit;
}

$model_type = $type === 'question' ? 'App\\Models\\Question' : 'App\\Models\\Answer';
$table = $type === 'question' ? 'questions' : 'answers';

// Get the item
$item = db_row("SELECT id,user_id,vote_count FROM $table WHERE id=?", [$mid]);
if (!$item) { echo json_encode(['error'=>'Not found.']); exit; }
if ($item['user_id'] == current_user_id()) { echo json_encode(['error'=>'You cannot vote on your own content.']); exit; }

// Check existing vote
$existing = db_row("SELECT id,value FROM votes WHERE user_id=? AND voteable_id=? AND voteable_type=?",
    [current_user_id(), $mid, $model_type]);

$new_vote = 0;
if ($existing) {
    if ((int)$existing['value'] === $value) {
        // Remove vote (toggle off)
        db_exec("DELETE FROM votes WHERE id=?", [$existing['id']]);
        db_exec("UPDATE $table SET vote_count=vote_count-? WHERE id=?", [$value,$mid]);
        if ($item['user_id'] != current_user_id())
            add_reputation($item['user_id'], -($value>0?($type==='question'?5:10):(-2)), 'Vote removed');
        $new_vote = 0;
    } else {
        // Flip vote
        db_exec("UPDATE votes SET value=? WHERE id=?", [$value,$existing['id']]);
        db_exec("UPDATE $table SET vote_count=vote_count+? WHERE id=?", [$value*2,$mid]);
        $new_vote = $value;
    }
} else {
    // New vote
    db_insert("INSERT INTO votes (user_id,voteable_id,voteable_type,value,created_at,updated_at) VALUES (?,?,?,?,NOW(),NOW())",
        [current_user_id(),$mid,$model_type,$value]);
    db_exec("UPDATE $table SET vote_count=vote_count+? WHERE id=?", [$value,$mid]);
    $rep_change = $type==='question' ? ($value>0?5:-2) : ($value>0?10:-2);
    if ($item['user_id'] != current_user_id())
        add_reputation($item['user_id'], $rep_change, ($value>0?'Upvote':'Downvote')." on $type");
    $new_vote = $value;
}

$updated = db_row("SELECT vote_count FROM $table WHERE id=?", [$mid]);
echo json_encode(['vote_count'=>$updated['vote_count'],'user_vote'=>$new_vote]);
