<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_role('instructor','admin'); csrf_check();
$aid = (int)($_POST['answer_id'] ?? 0);
$qid = (int)($_POST['question_id'] ?? 0);
$ans = db_row("SELECT * FROM answers WHERE id=?", [$aid]);
if ($ans) {
    $new = $ans['is_instructor_verified'] ? 0 : 1;
    db_exec("UPDATE answers SET is_instructor_verified=? WHERE id=?", [$new,$aid]);
    if ($new && $ans['user_id'] != current_user_id())
        add_reputation($ans['user_id'], 20, 'Answer instructor-verified');
    flash('success', $new ? 'Answer verified!' : 'Verification removed.');
}
redirect("question.php?id=$qid");
