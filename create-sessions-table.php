<?php
// Create sessions table for Vercel fix
require_once 'includes/db.php';

$sql = "
CREATE TABLE IF NOT EXISTS sessions (
  id CHAR(64) PRIMARY KEY,
  user_id INT NOT NULL,
  ip_address VARCHAR(45),
  user_agent TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  expires_at TIMESTAMP NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

DELETE FROM sessions WHERE expires_at < NOW();
";

try {
    db_exec($sql);
    echo "✅ Sessions table created!";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>

