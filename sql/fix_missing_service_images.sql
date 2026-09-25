-- Run once on an existing database to replace the two removed image files.
-- Both replacement files are already present in the project's img directory.
UPDATE services
SET image_url = CASE name
    WHEN 'Lip Blush' THEN '../img/microblading.png'
    WHEN 'Eyeliner Tattoo' THEN '../img/cat eye look.png'
    ELSE image_url
END
WHERE name IN ('Lip Blush', 'Eyeliner Tattoo')
  AND image_url IN ('../img/lip blush.png', '../img/eyeliner tattoo.png');
