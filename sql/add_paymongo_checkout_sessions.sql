-- Run once on an existing Cataleya database before enabling PayMongo checkout.
-- This records the server-validated booking draft until PayMongo reports paid.
ALTER TABLE payments
    MODIFY payment_method ENUM('cash', 'gcash', 'qrph', 'card', 'bank_transfer') NOT NULL;

CREATE TABLE IF NOT EXISTS payment_checkouts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    checkout_session_id VARCHAR(100) NOT NULL,
    return_token CHAR(64) NOT NULL,
    reference_number VARCHAR(100) NOT NULL,
    idempotency_key VARCHAR(128) NOT NULL,
    user_id INT NOT NULL,
    service_id INT NOT NULL,
    staff_id INT NULL,
    slot_id INT NOT NULL,
    booking_date DATE NOT NULL,
    booking_time TIME NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    special_requests TEXT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    downpayment_amount DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(50) NULL,
    payment_id VARCHAR(100) NULL,
    status ENUM('created','paid','fulfilled','failed','expired','conflict') NOT NULL DEFAULT 'created',
    booking_id INT NULL,
    paid_at DATETIME NULL,
    fulfilled_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_payment_checkouts_session (checkout_session_id),
    UNIQUE KEY uq_payment_checkouts_return_token (return_token),
    UNIQUE KEY uq_payment_checkouts_reference (reference_number),
    UNIQUE KEY uq_payment_checkouts_idempotency (idempotency_key),
    UNIQUE KEY uq_payment_checkouts_booking (booking_id),
    KEY idx_payment_checkouts_user_status (user_id, status),
    CONSTRAINT fk_payment_checkouts_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_payment_checkouts_service FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
    CONSTRAINT fk_payment_checkouts_staff FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE SET NULL,
    CONSTRAINT fk_payment_checkouts_slot FOREIGN KEY (slot_id) REFERENCES time_slots(id) ON DELETE RESTRICT,
    CONSTRAINT fk_payment_checkouts_booking FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
