<?php
// logout.php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
if ($_SERVER['REQUEST_METHOD']==='POST') { csrf_check(); }
session_destroy();
redirect('login.php');
