-- Migration to make booking_date column more flexible
-- This changes the column from DATE to VARCHAR to accept various date formats
-- Note: It's generally better to keep DATE type and format dates in the application,
-- but this provides flexibility if needed.

ALTER TABLE bookings MODIFY COLUMN booking_date VARCHAR(50) NOT NULL;
