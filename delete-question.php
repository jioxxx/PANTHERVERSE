<?php
// delete-question.php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_login(); csrf_check();
$id = (int)($_POST['id'] ?? 0);
$q  = db_row("SELECT user_id FROM questions WHERE id=? AND deleted_at IS NULL", [$id]);
if ($q && (current_user_id()==$q['user_id'] || current_user_role()==='admin')) {
    db_exec("UPDATE questions SET deleted_at=NOW() WHERE id=?", [$id]);
    flash('success','Question deleted.');
    redirect('questions.php');
}
redirect("question.php?id=$id");
