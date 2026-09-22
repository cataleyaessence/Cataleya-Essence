-- Populate services table with services from serv.php
-- This ensures the service_id lookups work correctly

USE cataleya_db;

-- Clear existing services (optional - comment out if you want to keep existing data)
-- TRUNCATE TABLE services;

-- Facial Services
INSERT INTO services (name, main_category, sub_category, price, duration_minutes, is_active) VALUES
('Signature Facial', 'Beauty Services', 'Facial Services', 599.00, 60, 1),
('Acne Clear Facial', 'Beauty Services', 'Facial Services', 599.00, 60, 1),
('Classic Facial', 'Beauty Services', 'Facial Services', 350.00, 45, 1),
('Hydra Facial', 'Beauty Services', 'Facial Services', 799.00, 75, 1),
('Diamond Peel', 'Beauty Services', 'Facial Services', 249.00, 30, 1),
('PDT Light Therapy', 'Beauty Services', 'Facial Services', 249.00, 30, 1),
('Pimple Injection', 'Beauty Services', 'Facial Services', 199.00, 15, 1),
('BB Glow', 'Beauty Services', 'Facial Services', 799.00, 60, 1),
('Warts Removal (Per Area)', 'Beauty Services', 'Facial Services', 1499.00, 30, 1),
('Vajacial', 'Beauty Services', 'Facial Services', 999.00, 45, 1);

-- RF + Lipo Cav
INSERT INTO services (name, main_category, sub_category, price, duration_minutes, is_active) VALUES
('RF Face', 'Beauty Services', 'RF + Lipo Cav', 299.00, 30, 1),
('RF Arms', 'Beauty Services', 'RF + Lipo Cav', 499.00, 45, 1),
('RF Belly', 'Beauty Services', 'RF + Lipo Cav', 599.00, 45, 1),
('RF Legs', 'Beauty Services', 'RF + Lipo Cav', 699.00, 60, 1),
('RF Whole Body', 'Beauty Services', 'RF + Lipo Cav', 1999.00, 90, 1);

-- EyeLash Enhancement
INSERT INTO services (name, main_category, sub_category, price, duration_minutes, is_active) VALUES
('Natural Look', 'Beauty Services', 'EyeLash Enhancement', 350.00, 60, 1),
('Volume Look', 'Beauty Services', 'EyeLash Enhancement', 450.00, 75, 1),
('Cat Eye Look', 'Beauty Services', 'EyeLash Enhancement', 550.00, 90, 1),
('Wispy Look', 'Beauty Services', 'EyeLash Enhancement', 800.00, 120, 1),
('Keratin lash Lift', 'Beauty Services', 'EyeLash Enhancement', 800.00, 60, 1),
('Last Lift/ Mascara', 'Beauty Services', 'EyeLash Enhancement', 350.00, 45, 1);

-- Hair Laser Removal
INSERT INTO services (name, main_category, sub_category, price, duration_minutes, is_active) VALUES
('Upper up', 'Beauty Services', 'Hair Laser Removal', 199.00, 15, 1),
('Face', 'Beauty Services', 'Hair Laser Removal', 299.00, 30, 1),
('Underarm', 'Beauty Services', 'Hair Laser Removal', 499.00, 30, 1),
('Arms', 'Beauty Services', 'Hair Laser Removal', 799.00, 45, 1),
('Legs', 'Beauty Services', 'Hair Laser Removal', 899.00, 60, 1),
('Chest', 'Beauty Services', 'Hair Laser Removal', 499.00, 30, 1),
('Brazilian', 'Beauty Services', 'Hair Laser Removal', 799.00, 45, 1),
('Whole Body', 'Beauty Services', 'Hair Laser Removal', 2500.00, 120, 1);

-- Laser Whitening
INSERT INTO services (name, main_category, sub_category, price, duration_minutes, is_active) VALUES
('Black Doll Carbon', 'Beauty Services', 'Laser Whitening', 799.00, 45, 1),
('Underarm Laser', 'Beauty Services', 'Laser Whitening', 499.00, 30, 1),
('Elbow Whitening', 'Beauty Services', 'Laser Whitening', 499.00, 30, 1),
('Knee Whitening', 'Beauty Services', 'Laser Whitening', 599.00, 30, 1),
('Melasma', 'Beauty Services', 'Laser Whitening', 799.00, 60, 1),
('Tattoo Removal', 'Beauty Services', 'Laser Whitening', 2500.00, 60, 1);

-- Mesolipo
INSERT INTO services (name, main_category, sub_category, price, duration_minutes, is_active) VALUES
('Double Chin', 'Beauty Services', 'Mesolipo', 999.00, 30, 1),
('Arms', 'Beauty Services', 'Mesolipo', 2000.00, 45, 1),
('Tummy', 'Beauty Services', 'Mesolipo', 1499.00, 45, 1),
('Thigh', 'Beauty Services', 'Mesolipo', 2000.00, 60, 1);

-- Gluta Whitening
INSERT INTO services (name, main_category, sub_category, price, duration_minutes, is_active) VALUES
('Ultra Whitening Drip / Session', 'Beauty Services', 'Gluta Whitening', 1799.00, 60, 1);

-- Semi-Permanent Make Up
INSERT INTO services (name, main_category, sub_category, price, duration_minutes, is_active) VALUES
('Microblading', 'Beauty Services', 'Semi-Permanent Make Up', 2999.00, 120, 1),
('Lip Blush', 'Beauty Services', 'Semi-Permanent Make Up', 2499.00, 90, 1),
('Eyeliner Tattoo', 'Beauty Services', 'Semi-Permanent Make Up', 1999.00, 60, 1);

-- Waxing
INSERT INTO services (name, main_category, sub_category, price, duration_minutes, is_active) VALUES
('Underarm Waxing', 'Beauty Services', 'Waxing', 199.00, 15, 1),
('Full Leg Waxing', 'Beauty Services', 'Waxing', 599.00, 45, 1),
('Brazilian Waxing', 'Beauty Services', 'Waxing', 799.00, 45, 1);

-- HIFU Ultheraphy
INSERT INTO services (name, main_category, sub_category, price, duration_minutes, is_active) VALUES
('HIFU Face', 'Beauty Services', 'HIFU Ultheraphy', 2999.00, 90, 1),
('HIFU Body', 'Beauty Services', 'HIFU Ultheraphy', 4999.00, 120, 1);

-- Nail Services
INSERT INTO services (name, main_category, sub_category, price, duration_minutes, is_active) VALUES
('Manicure', 'Beauty Services', 'Nail Services', 199.00, 30, 1),
('Pedicure', 'Beauty Services', 'Nail Services', 249.00, 45, 1),
('Gel Manicure', 'Beauty Services', 'Nail Services', 399.00, 45, 1),
('Gel Pedicure', 'Beauty Services', 'Nail Services', 499.00, 60, 1);

-- Body Care (Spa Massage)
INSERT INTO services (name, main_category, sub_category, price, duration_minutes, is_active) VALUES
('Swedish Massage', 'Spa Massage', 'Body Care', 999.00, 60, 1),
('Deep Tissue Massage', 'Spa Massage', 'Body Care', 1299.00, 90, 1),
('Aromatherapy Massage', 'Spa Massage', 'Body Care', 1199.00, 60, 1);

-- Traditional Body Care (Spa Massage)
INSERT INTO services (name, main_category, sub_category, price, duration_minutes, is_active) VALUES
('Hilot', 'Spa Massage', 'Traditional Body Care', 899.00, 60, 1),
('Ventosa', 'Spa Massage', 'Traditional Body Care', 799.00, 45, 1);

-- Body Skin Treatment (Spa Massage)
INSERT INTO services (name, main_category, sub_category, price, duration_minutes, is_active) VALUES
('Body Scrub', 'Spa Massage', 'Body Skin Treatment', 699.00, 45, 1),
('Body Wrap', 'Spa Massage', 'Body Skin Treatment', 999.00, 60, 1);
