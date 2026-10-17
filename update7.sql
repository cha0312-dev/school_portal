USE school_portal;

-- Run this once. It adds the extra profile details (existing accounts keep working).
ALTER TABLE users
  ADD COLUMN birthdate DATE NULL,
  ADD COLUMN address VARCHAR(200) NULL,
  ADD COLUMN guardian_name VARCHAR(100) NULL,
  ADD COLUMN guardian_phone VARCHAR(30) NULL,
  ADD COLUMN job_title VARCHAR(80) NULL;