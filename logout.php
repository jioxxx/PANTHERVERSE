
<?php
// logout.php - Cookie Compatible
require_once 'includes/auth.php';
if ($_SERVER['REQUEST_METHOD']==='POST') { csrf_check(); }
logout_user();
redirect('login.php');

