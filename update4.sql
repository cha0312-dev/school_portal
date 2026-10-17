USE school_portal;

-- Run this once. Existing accounts keep working (the new columns stay empty for them).
ALTER TABLE users
  ADD COLUMN school_id VARCHAR(30) NULL UNIQUE,
  ADD COLUMN grade_level VARCHAR(20) NULL,
  ADD COLUMN phone VARCHAR(30) NULL;