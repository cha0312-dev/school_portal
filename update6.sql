USE school_portal;

-- Days with no classes that a teacher marks for the whole school (weather, events, trainings, moving holidays...)
CREATE TABLE IF NOT EXISTS school_days_off (
  day DATE PRIMARY KEY,
  reason VARCHAR(100) NOT NULL,
  marked_by INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);