-- PANTHERVERSE: DB Sessions for Vercel compatibility
-- Run: php run-sql.php migrations/add_sessions_table.sql

CREATE TABLE IF NOT EXISTS sessions (
  id CHAR(64) PRIMARY KEY,
  user_id INT NOT NULL,
  ip_address VARCHAR(45),
  user_agent TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  expires_at TIMESTAMP NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_user (user_id),
  INDEX idx_expires (expires_at)
);

-- Clean expired
DELETE FROM sessions WHERE expires_at < NOW();

