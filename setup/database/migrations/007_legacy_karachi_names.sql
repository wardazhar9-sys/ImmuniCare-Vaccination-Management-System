-- Use established Karachi names from the original database seed.

START TRANSACTION;

UPDATE users
SET name = 'ImmuniCare Admin'
WHERE email = 'sana.ahmed@immunicare.local';

UPDATE children c
JOIN users u ON u.id = c.parent_id
SET c.gender = CASE c.child_name
    WHEN 'Hamza Khan' THEN 'Male'
    WHEN 'Hira Khan' THEN 'Male'
    ELSE c.gender
END
WHERE u.email = 'ayesha.khan@immunicare.local';

UPDATE children c
JOIN users u ON u.id = c.parent_id
SET c.child_name = CASE c.child_name
    WHEN 'Maryam Khan' THEN 'Maryam'
    WHEN 'Hamza Khan' THEN 'Ismail'
    WHEN 'Eman Khan' THEN 'Sara'
    WHEN 'Zain Khan' THEN 'Musfirah'
    WHEN 'Hira Khan' THEN 'Muhammad Umar'
    ELSE c.child_name
END
WHERE u.email = 'ayesha.khan@immunicare.local';

UPDATE children c
JOIN users u ON u.id = c.parent_id
SET c.child_name = 'Areeba'
WHERE u.email = 'muhammad.ali@immunicare.local'
  AND c.child_name = 'Usman Ali';

UPDATE hospitals h
JOIN users u ON u.id = h.user_id
SET h.hospital_name = 'Aga Khan University Hospital'
WHERE u.email = 'aga.khan@immunicare.local';

UPDATE hospitals h
JOIN users u ON u.id = h.user_id
SET h.hospital_name = 'Liaquat National Hospital'
WHERE u.email = 'liaquat.national@immunicare.local';

INSERT IGNORE INTO schema_migrations (version)
VALUES ('007_legacy_karachi_names');

COMMIT;
