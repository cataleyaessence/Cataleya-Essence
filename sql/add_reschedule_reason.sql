-- Run once on an existing database to retain the customer's reschedule reason.
ALTER TABLE bookings
    ADD COLUMN reschedule_reason VARCHAR(1000) NULL AFTER notes;
