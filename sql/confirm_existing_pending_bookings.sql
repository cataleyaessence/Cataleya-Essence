-- Customer bookings have a completed 50% downpayment at creation, so they
-- are confirmed immediately instead of waiting for a separate admin action.
UPDATE bookings
SET status = 'confirmed', updated_at = NOW()
WHERE status = 'pending';
