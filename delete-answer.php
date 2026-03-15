<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_login(); csrf_check();

$aid = (int)($_POST['answer_id'] ?? 0);
$qid = (int)($_POST['question_id'] ?? 0);
$ans = db_row("SELECT * FROM answers WHERE id=? AND deleted_at IS NULL", [$aid]);
if ($ans && (current_user_id()==$ans['user_id'] || current_user_role()==='admin')) {
    db_exec("UPDATE answers SET deleted_at=NOW() WHERE id=?", [$aid]);
    flash('success','Answer deleted.');
}
redirect("question.php?id=$qid");
