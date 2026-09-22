-- Update staff table to add new columns for category specialization
ALTER TABLE staff 
ADD COLUMN category ENUM('Beauty Services', 'Spa Massage') NOT NULL DEFAULT 'Beauty Services',
ADD COLUMN title VARCHAR(100),
ADD COLUMN image_url VARCHAR(255),
ADD COLUMN experience_years INT DEFAULT 0;

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
