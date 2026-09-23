-- Run once on an existing database to support customer rescheduling.
ALTER TABLE bookings
    MODIFY status ENUM('pending', 'confirmed', 'rescheduled', 'completed', 'cancelled')
    NOT NULL DEFAULT 'confirmed';
