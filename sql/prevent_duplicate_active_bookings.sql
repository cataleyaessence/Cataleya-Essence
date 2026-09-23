-- Run once on existing databases after removing any already-duplicated active bookings.
-- It permits a new booking after cancellation while preventing a user from having
-- two active bookings in the same date and time.
ALTER TABLE bookings
    ADD COLUMN active_user_slot_key VARCHAR(128)
        GENERATED ALWAYS AS (
            CASE WHEN status IN ('confirmed', 'rescheduled')
                THEN CONCAT(user_id, '|', booking_date, '|', booking_time)
                ELSE NULL
            END
        ) STORED,
    ADD UNIQUE KEY uq_bookings_active_user_slot (active_user_slot_key);
