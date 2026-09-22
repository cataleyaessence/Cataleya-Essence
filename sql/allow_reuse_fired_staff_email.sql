-- A fired staff member keeps their record for booking history, but their
-- previous email can be reused when a new staff member is added.
ALTER TABLE staff
    MODIFY COLUMN email VARCHAR(100) NULL;

UPDATE staff
SET email = NULL
WHERE is_active = 0;
