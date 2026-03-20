<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: text/html');
require_once 'includes/session.php';
require_once 'includes/db.php';

if (!isset($_GET['key']) || $_GET['key'] !== 'pantherverse2024') die('?key=pantherverse2024 required');

echo "<h1>🛠️ PANTHERVERSE SEED TOOL</h1>";

// Check DB
$user = db_row("SELECT * FROM users LIMIT 1");
if ($user) {
  echo "<p style='color:green'>✅ DB OK - Users exist</p>";
} else {
  // Create minimal
  db_exec("CREATE TABLE IF NOT EXISTS users (id serial PRIMARY KEY, email varchar UNIQUE, password varchar(255), is_active boolean DEFAULT true)");
  $hash = password_hash('Admin@12345', PASSWORD_BCRYPT);
  db_exec("INSERT INTO users (email, password, role) VALUES ('admin@pantherverse.jrmsu.edu.ph', '$hash', 'admin') ON CONFLICT DO NOTHING");
  echo "<p style='color:green'>✅ Seeded admin: Admin@12345</p>";
}

echo "<p><a href='login.php'>→ Test Login</a></p>";
?>

