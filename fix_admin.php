<?php
require_once 'includes/db.php';

// Reset admin account
$email = 'admin@pantherverse.jrmsu.edu.ph';
$pass = password_hash('Admin@12345', PASSWORD_DEFAULT);
db_exec("UPDATE users SET password = ?, is_active = 1 WHERE email = ?", [$pass, $email]);
echo "Admin password reset to Admin@12345. Try logging in now.";
?>

