-- Run once on an existing database. These filenames must match the image
-- assets exactly for case-sensitive web servers.
UPDATE services
SET image_url = CASE
    WHEN name = 'Hilot'
         AND main_category = 'Spa Massage'
         AND sub_category = 'Traditional Body Care' THEN '../img/Hilot.png'
    WHEN name = 'Ventosa'
         AND main_category = 'Spa Massage'
         AND sub_category = 'Traditional Body Care' THEN '../img/Ventosa.png'
    WHEN name = 'Arms'
         AND main_category = 'Beauty Services'
         AND sub_category = 'Hair Laser Removal' THEN '../img/removal arms.png'
    WHEN name = 'Arms'
         AND main_category = 'Beauty Services'
         AND sub_category = 'Mesolipo' THEN '../img/arms gluta.png'
    ELSE image_url
END
WHERE (name IN ('Hilot', 'Ventosa')
       AND main_category = 'Spa Massage'
       AND sub_category = 'Traditional Body Care')
   OR (name = 'Arms'
       AND main_category = 'Beauty Services'
       AND sub_category IN ('Hair Laser Removal', 'Mesolipo'));
