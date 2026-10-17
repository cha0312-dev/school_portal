USE school_portal;

-- Run this once. It lets people reset a forgotten password with a link that expires.
CREATE TABLE IF NOT EXISTS password_resets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  token_hash CHAR(64) NULL,          -- only a fingerprint of the link's secret is kept, never the secret itself
  ip VARCHAR(45) NOT NULL,
  expires_at DATETIME NULL,
  used_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (token_hash), INDEX (ip, created_at), INDEX (user_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);