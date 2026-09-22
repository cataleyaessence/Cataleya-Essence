-- Soft-removal status for staff who have left the business.
-- Inactive staff stay attached to historical bookings but are excluded from new assignments.
ALTER TABLE staff
    ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER is_available;

CREATE INDEX idx_staff_active_catalog
    ON staff (is_active, category, is_available, full_name);
