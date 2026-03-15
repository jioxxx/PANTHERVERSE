<?php
// download-resource.php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_login();

$id = (int)($_GET['id'] ?? 0);
$r  = db_row("SELECT * FROM resources WHERE id=? AND deleted_at IS NULL", [$id]);
if (!$r) { flash('error','Resource not found.'); redirect('resources.php'); }

db_exec("UPDATE resources SET download_count=download_count+1 WHERE id=?", [$id]);
flash('success','Download counted! In production, files would be served from storage.');
redirect('resources.php');
