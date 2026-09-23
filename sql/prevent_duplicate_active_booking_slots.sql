-- Run once after confirming there are no duplicate confirmed/rescheduled
-- appointments with the same date and time. This allows multiple users on the
-- same date while reserving every active appointment time for one user only.
ALTER TABLE bookings
    ADD COLUMN active_slot_key VARCHAR(128)
        GENERATED ALWAYS AS (
            CASE WHEN status IN ('confirmed', 'rescheduled')
                THEN CONCAT(booking_date, '|', booking_time)
                ELSE NULL
            END
        ) STORED,
    ADD UNIQUE KEY uq_bookings_active_slot (active_slot_key);
