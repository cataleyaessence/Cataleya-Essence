-- Run once on an existing database to give shared Beauty Service images
-- distinct, matching assets.
UPDATE services
SET image_url = CASE
    WHEN name = 'Underarm' AND sub_category = 'Hair Laser Removal' THEN '../img/removal underarm.png'
    WHEN name = 'Lip Blush' AND sub_category = 'Semi-Permanent Make Up' THEN '../img/microshading.png'
    WHEN name = 'Underarm Waxing' THEN '../img/underarm.png'
    WHEN name = 'Full Leg Waxing' THEN '../img/legs.png'
    WHEN name = 'Brazilian Waxing' THEN '../img/brazilian.png'
    ELSE image_url
END
WHERE main_category = 'Beauty Services'
  AND (
      (name = 'Underarm' AND sub_category = 'Hair Laser Removal' AND image_url = '../img/removal arms.png')
      OR (name = 'Lip Blush' AND sub_category = 'Semi-Permanent Make Up' AND image_url = '../img/microblading.png')
      OR name IN ('Underarm Waxing', 'Full Leg Waxing', 'Brazilian Waxing')
  );
