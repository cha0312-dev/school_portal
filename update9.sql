USE school_portal;

-- Run this once. It adds school fees and student payments.

-- A fee that students owe. "applies_to" decides who owes it:
--   school  = every student
--   grade   = students in one grade or year level (grade_level)
--   class   = students in one class or room (class_id)
--   subject = students in any class of one subject (subject)
CREATE TABLE IF NOT EXISTS fees (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(100) NOT NULL,
  description VARCHAR(255) NULL,
  category ENUM('tuition','miscellaneous','laboratory','books','uniform','activity','exam','other') NOT NULL DEFAULT 'other',
  amount DECIMAL(10,2) NOT NULL,
  applies_to ENUM('school','grade','class','subject') NOT NULL DEFAULT 'school',
  grade_level VARCHAR(20) NULL,
  class_id INT NULL,
  subject VARCHAR(80) NULL,
  due_date DATE NULL,
  allow_partial TINYINT(1) NOT NULL DEFAULT 1,   -- 1 = students may pay in installments
  active TINYINT(1) NOT NULL DEFAULT 1,          -- 0 = archived (kept because payments point to it)
  created_by INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- One payment toward one fee. Students send "pending" payments with a reference number;
-- a teacher checks it and confirms (a receipt number is given) or rejects it.
-- Cash paid at the office is recorded by the teacher and is confirmed right away.
CREATE TABLE IF NOT EXISTS payments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  fee_id INT NOT NULL,
  student_id INT NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  method ENUM('gcash','maya','bank','cash','online') NOT NULL,
  reference VARCHAR(60) NULL,
  status ENUM('pending','confirmed','rejected') NOT NULL DEFAULT 'pending',
  note VARCHAR(200) NULL,                        -- why it was rejected, or a remark from the teacher
  receipt_no VARCHAR(30) NULL UNIQUE,
  decided_by INT NULL,
  decided_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (student_id, status), INDEX (fee_id, status),
  FOREIGN KEY (fee_id) REFERENCES fees(id) ON DELETE CASCADE,
  FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (decided_by) REFERENCES users(id) ON DELETE SET NULL
);
