CREATE DATABASE IF NOT EXISTS school_portal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE school_portal;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','teacher','student','pending_teacher') NOT NULL DEFAULT 'student',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS scores (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  teacher_id INT NOT NULL,
  subject VARCHAR(80) NOT NULL,
  quarter TINYINT NOT NULL,
  details TEXT NOT NULL,                 -- the raw scores, e.g. {"quizzes":[42,50],...}
  quarter_grade DECIMAL(5,2) NOT NULL,   -- computed by the server
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY one_per_quarter (student_id, subject, quarter),
  FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE
);