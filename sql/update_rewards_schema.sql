-- Update rewards table for new rewards program spec
USE cataleya_db;

-- Add last_birthday_bonus_year column
ALTER TABLE rewards ADD COLUMN last_birthday_bonus_year YEAR DEFAULT NULL AFTER visits;

-- Note: visits column should already exist from previous migration
-- If it doesn't exist, uncomment the line below:
-- ALTER TABLE rewards ADD COLUMN visits INT DEFAULT 0 AFTER points;
