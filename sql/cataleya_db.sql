CREATE DATABASE IF NOT EXISTS cataleya_db;
USE cataleya_db;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NULL,
    password_hash VARCHAR(255) NOT NULL,
    is_verified BOOLEAN DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE otp_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    otp_code VARCHAR(6) NOT NULL,
    purpose ENUM('signup', 'reset') NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Services Table
CREATE TABLE services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    main_category ENUM('Beauty Services', 'Spa Massage') NOT NULL,
    sub_category VARCHAR(100) NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    duration_minutes INT,
    image_url VARCHAR(255),
    is_active BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Staff/Therapists Table
CREATE TABLE staff (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    specialization VARCHAR(255),
    category ENUM('Beauty Services', 'Spa Massage') NOT NULL,
    title VARCHAR(100),
    image_url VARCHAR(255),
    experience_years INT DEFAULT 0,
    is_available BOOLEAN DEFAULT 1,
    rating DECIMAL(3, 2) DEFAULT 0.00,
    total_reviews INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bookings/Appointments Table
CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    service_id INT NOT NULL,
    staff_id INT,
    booking_date DATE NOT NULL,
    booking_time TIME NOT NULL,
    deadline DATETIME DEFAULT NULL,
    status ENUM('pending', 'confirmed', 'rescheduled', 'completed', 'cancelled') DEFAULT 'confirmed',
    auto_cancelled BOOLEAN DEFAULT 0,
    total_amount DECIMAL(10, 2) NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    active_user_slot_key VARCHAR(128) GENERATED ALWAYS AS (
        CASE WHEN status IN ('confirmed', 'rescheduled')
            THEN CONCAT(user_id, '|', booking_date, '|', booking_time)
            ELSE NULL
        END
    ) STORED,
    active_slot_key VARCHAR(128) GENERATED ALWAYS AS (
        CASE WHEN status IN ('confirmed', 'rescheduled')
            THEN CONCAT(booking_date, '|', booking_time)
            ELSE NULL
        END
    ) STORED,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
    FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE SET NULL,
    UNIQUE KEY uq_bookings_active_user_slot (active_user_slot_key),
    UNIQUE KEY uq_bookings_active_slot (active_slot_key)
);

-- Reviews/Ratings Table
CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    booking_id INT NOT NULL,
    staff_id INT,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE SET NULL
);

-- Rewards Table
CREATE TABLE rewards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    points INT DEFAULT 0,
    tier ENUM('bronze', 'silver', 'gold', 'platinum') DEFAULT 'bronze',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY (user_id)
);

-- Reward Transactions Table
CREATE TABLE reward_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    points_earned INT DEFAULT 0,
    points_redeemed INT DEFAULT 0,
    description VARCHAR(255),
    booking_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE SET NULL
);

-- Payments Table
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    user_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    payment_method ENUM('cash', 'gcash', 'card', 'bank_transfer') NOT NULL,
    status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    transaction_id VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Inventory Table
CREATE TABLE inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_name VARCHAR(255) NOT NULL,
    description TEXT,
    quantity INT NOT NULL DEFAULT 0,
    unit_price DECIMAL(10, 2) NOT NULL,
    category VARCHAR(100),
    low_stock_threshold INT DEFAULT 10,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Inventory Transactions Table
CREATE TABLE inventory_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    inventory_id INT NOT NULL,
    transaction_type ENUM('in', 'out') NOT NULL,
    quantity INT NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (inventory_id) REFERENCES inventory(id) ON DELETE CASCADE
);

-- Feedback Table
CREATE TABLE feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('pending', 'reviewed', 'resolved') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Staff Availability Table
CREATE TABLE staff_availability (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    day_of_week ENUM('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    is_available BOOLEAN DEFAULT 1,
    FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE,
    UNIQUE KEY (staff_id, day_of_week)
);

-- Time Slots Table (defines available time slots)
CREATE TABLE time_slots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slot_time TIME NOT NULL UNIQUE,
    display_time VARCHAR(20) NOT NULL,
    is_active BOOLEAN DEFAULT 1,
    sort_order INT DEFAULT 0
);

-- Daily Slot Availability Table (tracks availability per date)
CREATE TABLE daily_slot_availability (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slot_date DATE NOT NULL,
    slot_id INT NOT NULL,
    status ENUM('available', 'filling', 'booked', 'unavailable') DEFAULT 'available',
    max_bookings INT DEFAULT 1,
    current_bookings INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (slot_id) REFERENCES time_slots(id) ON DELETE CASCADE,
    UNIQUE KEY (slot_date, slot_id)
);

-- Insert default time slots
INSERT INTO time_slots (slot_time, display_time, sort_order) VALUES
('09:00:00', '9:00 AM', 1),
('10:00:00', '10:00 AM', 2),
('11:00:00', '11:00 AM', 3),
('12:00:00', '12:00 PM', 4),
('13:00:00', '1:00 PM', 5),
('14:00:00', '2:00 PM', 6),
('15:00:00', '3:00 PM', 7),
('16:00:00', '4:00 PM', 8),
('17:00:00', '5:00 PM', 9),
('18:00:00', '6:00 PM', 10);

-- Insert sample therapists
INSERT INTO staff (full_name, email, phone, category, title, image_url, experience_years, rating, total_reviews) VALUES
-- Beauty Services Therapists
('Jhacel', 'jhacel@cataleya.com', '09171234567', 'Beauty Services', 'Aesthetician', '../img/Jhacel (1).png', 8, 4.9, 45),
('Cyrel', 'cyrel@cataleya.com', '09181234567', 'Beauty Services', 'Aesthetician', '../img/curel.png', 8, 4.9, 38),
('Joy', 'joy@cataleya.com', '09191234567', 'Beauty Services', 'Aesthetician', '../img/joy.png', 8, 4.9, 42),
('Coleen', 'coleen@cataleya.com', '09201234567', 'Beauty Services', 'Aesthetician', '../img/coleen.png', 8, 4.9, 35),
('Jade', 'jade@cataleya.com', '09211234567', 'Beauty Services', 'Aesthetician', '../img/jade.png', 8, 4.9, 40),
('Aira', 'aira@cataleya.com', '09221234567', 'Beauty Services', 'Aesthetician', '../img/aira.png', 8, 4.9, 33),
-- Spa Massage Therapists
('Melvin', 'melvin@cataleya.com', '09231234567', 'Spa Massage', 'Massage Therapist', '../img/Melvin.png', 8, 4.8, 28),
('Megan', 'megan@cataleya.com', '09241234567', 'Spa Massage', 'Massage Therapist', '../img/megan.png', 8, 4.9, 31),
('Dimple', 'dimple@cataleya.com', '09251234567', 'Spa Massage', 'Massage Therapist', '../img/Dimple.png', 8, 4.7, 25),
('Roxanne', 'roxanne@cataleya.com', '09261234567', 'Spa Massage', 'Massage Therapist', '../img/Roxanne.png', 8, 4.9, 29),
('Hajie', 'hajie@cataleya.com', '09271234567', 'Spa Massage', 'Massage Therapist', '../img/Hajie.png', 8, 4.8, 22),
('Meah', 'meah@cataleya.com', '09281234567', 'Spa Massage', 'Massage Therapist', '../img/Rectangle 252.png', 8, 4.9, 27),
('Yurie', 'yurie@cataleya.com', '09291234567', 'Spa Massage', 'Massage Therapist', '../img/Yurie.png', 8, 4.7, 24),
('Trixie', 'trixie@cataleya.com', '09301234567', 'Spa Massage', 'Massage Therapist', '../img/trixie.png', 8, 4.9, 30),
('Joy', 'joy.spa@cataleya.com', '09311234567', 'Spa Massage', 'Massage Therapist', '../img/Joy...png', 8, 4.8, 26),
('Jade', 'jade.spa@cataleya.com', '09321234567', 'Spa Massage', 'Massage Therapist', '../img/Jade...png', 8, 4.9, 32),
('Coleen', 'coleen.spa@cataleya.com', '09331234567', 'Spa Massage', 'Massage Therapist', '../img/Coleen...png', 8, 4.7, 21);
