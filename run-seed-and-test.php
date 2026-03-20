<?php
/**
 * PANTHERVERSE — VER CEL SEED + TEST
 * Visit once to seed DB + test login
 */
require_once 'includes/session.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

echo "<h1>🐆 PANTHERVERSE VER CEL FIXER</h1><hr>";
echo "<h3>1. Check DB Connection</h3>";
try {
  $test = db_row('SELECT version()');
  echo "<p style='color:green'>✅ DB Connected: " . htmlspecialchars($test['version'] ?? 'MySQL') . "</p>";
} catch (Exception $e) {
  die("<p style='color:red'>❌ DB Error: " . htmlspecialchars($e->getMessage()) . "</p>");
}

echo "<h3>2. Create Users Table + Admin</h3>";
try {
  $bool_true = $GLOBALS['_sql_true'];
  db_exec("CREATE TABLE IF NOT EXISTS users (
    id BIGSERIAL PRIMARY KEY,
    email VARCHAR(200) UNIQUE, 
    password VARCHAR(255),
    is_active $bool_true DEFAULT $bool_true,
    role VARCHAR(20) DEFAULT 'student'
  )");
  
  $hash = password_hash('Admin@12345', PASSWORD_BCRYPT);
  db_exec("DELETE FROM users WHERE email = 'admin@pantherverse.jrmsu.edu.ph'");
  db_exec("INSERT INTO users (email, password, role) VALUES ('admin@pantherverse.jrmsu.edu.ph', '$hash', 'admin')");
  
  echo "<p style='color:green'>✅ Admin created!</p>";
} catch (Exception $e) {
  echo "<p style='color:orange'>⚠️ Users table issue: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<h3>3. Test Login</h3>";
$_POST['email'] = 'admin@pantherverse.jrmsu.edu.ph';
$_POST['password'] = 'Admin@12345';
$_SERVER['REQUEST_METHOD'] = 'POST';

ob_start();
require 'login.php';
$output = ob_get_clean();

if (strpos($output, 'Invalid email or password') === false) {
  echo "<p style='color:green'>✅ Login WORKS!</p>";
} else {
  echo "<p style='color:red'>❌ Login FAILED</p>";
}

echo "<hr><p><strong>DELETE THIS FILE AFTER! Run: git rm run-seed-and-test.php && git push</strong></p>";
?>

