-- Add profile_photo column to users table
ALTER TABLE users ADD COLUMN profile_photo VARCHAR(255) DEFAULT NULL AFTER password_hash;
