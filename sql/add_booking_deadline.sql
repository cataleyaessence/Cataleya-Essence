-- Add deadline column to bookings table
USE cataleya_db;

ALTER TABLE bookings ADD COLUMN deadline DATETIME DEFAULT NULL AFTER booking_time;
ALTER TABLE bookings ADD COLUMN auto_cancelled BOOLEAN DEFAULT 0 AFTER status;
