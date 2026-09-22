-- Update users table to add profile fields
USE cataleya_db;

-- Add phone and address fields to users table
ALTER TABLE users 
ADD COLUMN phone VARCHAR(20) AFTER email,
ADD COLUMN address_line1 VARCHAR(255) AFTER phone,
ADD COLUMN address_line2 VARCHAR(255) AFTER address_line1,
ADD COLUMN city VARCHAR(100) AFTER address_line2,
ADD COLUMN province VARCHAR(100) AFTER city,
ADD COLUMN postal_code VARCHAR(20) AFTER province,
ADD COLUMN profile_photo VARCHAR(255) AFTER postal_code;

-- Update existing user with sample data (optional)
UPDATE users 
SET phone = '+63 912 345 6789',
    address_line1 = '123 Main St.',
    address_line2 = 'Unit 4B',
    city = 'Baliwag',
    province = 'Bulacan',
    postal_code = '3006'
WHERE email = 'neilivan@gmail.com';
