-- Add visits column to rewards table
USE cataleya_db;

ALTER TABLE rewards ADD COLUMN visits INT DEFAULT 0 AFTER points;
