-- Repeatable demo scenario data.
-- Demo-owned rows are rebuilt; unrelated application data is preserved.

START TRANSACTION;

INSERT INTO users
    (name, email, password, role, status, email_verified_at)
VALUES
    ('Demo Admin', 'demo.admin@immunicare.local', '$2y$12$EHQ9dvv951pE1iqa6TQAwO81i.1ESJdaEhUd1hSHhITI/pLViLXq', 'admin', 'Active', NOW()),
    ('Demo Parent One', 'demo.parent1@immunicare.local', '$2y$12$EHQ9dvv951pE1iqa6TQAwO81i.1ESJdaEhUd1hSHhITI/pLViLXq', 'parent', 'Active', NOW()),
    ('Demo Parent Two', 'demo.parent2@immunicare.local', '$2y$12$EHQ9dvv951pE1iqa6TQAwO81i.1ESJdaEhUd1hSHhITI/pLViLXq', 'parent', 'Active', NOW()),
    ('Demo Hospital Alpha Account', 'demo.hospital.alpha@immunicare.local', '$2y$12$EHQ9dvv951pE1iqa6TQAwO81i.1ESJdaEhUd1hSHhITI/pLViLXq', 'hospital', 'Active', NOW()),
    ('Demo Hospital Beta Account', 'demo.hospital.beta@immunicare.local', '$2y$12$EHQ9dvv951pE1iqa6TQAwO81i.1ESJdaEhUd1hSHhITI/pLViLXq', 'hospital', 'Active', NOW())
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    role = VALUES(role),
    status = 'Active',
    email_verified_at = COALESCE(email_verified_at, NOW());

-- Remove orphaned rows before rebuilding demo-owned records.
DELETE FROM inventory_transactions
WHERE hospital_id NOT IN (SELECT id FROM hospitals)
   OR vaccine_id NOT IN (SELECT id FROM vaccines)
   OR (booking_id IS NOT NULL AND booking_id NOT IN (SELECT id FROM bookings))
   OR (vaccination_record_id IS NOT NULL AND vaccination_record_id NOT IN (SELECT id FROM vaccination_records));

DELETE FROM vaccination_records
WHERE child_id NOT IN (SELECT id FROM children)
   OR vaccine_id NOT IN (SELECT id FROM vaccines)
   OR hospital_id NOT IN (SELECT id FROM hospitals);

DELETE FROM vaccination_schedules
WHERE child_id NOT IN (SELECT id FROM children)
   OR vaccine_id NOT IN (SELECT id FROM vaccines)
   OR hospital_id NOT IN (SELECT id FROM hospitals);

DELETE FROM bookings
WHERE parent_id NOT IN (SELECT id FROM users)
   OR child_id NOT IN (SELECT id FROM children)
   OR hospital_id NOT IN (SELECT id FROM hospitals)
   OR vaccine_id NOT IN (SELECT id FROM vaccines);

DELETE FROM notifications
WHERE user_id NOT IN (SELECT id FROM users);

DELETE FROM notification_outbox
WHERE user_id NOT IN (SELECT id FROM users);

-- Clear previous demo-owned operational data in dependency order.
DELETE t
FROM inventory_transactions t
JOIN hospitals h ON h.id = t.hospital_id
JOIN users u ON u.id = h.user_id
WHERE u.email IN (
    'demo.hospital.alpha@immunicare.local',
    'demo.hospital.beta@immunicare.local'
);

DELETE vr
FROM vaccination_records vr
JOIN children c ON c.id = vr.child_id
JOIN users u ON u.id = c.parent_id
WHERE u.email IN (
    'demo.parent1@immunicare.local',
    'demo.parent2@immunicare.local'
);

DELETE vs
FROM vaccination_schedules vs
JOIN children c ON c.id = vs.child_id
JOIN users u ON u.id = c.parent_id
WHERE u.email IN (
    'demo.parent1@immunicare.local',
    'demo.parent2@immunicare.local'
);

DELETE b
FROM bookings b
JOIN users u ON u.id = b.parent_id
WHERE u.email IN (
    'demo.parent1@immunicare.local',
    'demo.parent2@immunicare.local'
);

DELETE n
FROM notifications n
JOIN users u ON u.id = n.user_id
WHERE u.email LIKE 'demo.%@immunicare.local';

DELETE o
FROM notification_outbox o
JOIN users u ON u.id = o.user_id
WHERE u.email LIKE 'demo.%@immunicare.local';

DELETE r
FROM report_exports r
JOIN users u ON u.id = r.requested_by
WHERE u.email = 'demo.admin@immunicare.local';

DELETE a
FROM audit_logs a
JOIN users u ON u.id = a.actor_user_id
WHERE u.email LIKE 'demo.%@immunicare.local';

DELETE h
FROM hospital_hours h
JOIN hospitals hospital ON hospital.id = h.hospital_id
JOIN users u ON u.id = hospital.user_id
WHERE u.email IN (
    'demo.hospital.alpha@immunicare.local',
    'demo.hospital.beta@immunicare.local'
);

DELETE h
FROM hospital_holidays h
JOIN hospitals hospital ON hospital.id = h.hospital_id
JOIN users u ON u.id = hospital.user_id
WHERE u.email IN (
    'demo.hospital.alpha@immunicare.local',
    'demo.hospital.beta@immunicare.local'
);

DELETE s
FROM hospital_slots s
JOIN hospitals hospital ON hospital.id = s.hospital_id
JOIN users u ON u.id = hospital.user_id
WHERE u.email IN (
    'demo.hospital.alpha@immunicare.local',
    'demo.hospital.beta@immunicare.local'
);

DELETE i
FROM hospital_inventory i
JOIN hospitals hospital ON hospital.id = i.hospital_id
JOIN users u ON u.id = hospital.user_id
WHERE u.email IN (
    'demo.hospital.alpha@immunicare.local',
    'demo.hospital.beta@immunicare.local'
);

DELETE c
FROM children c
JOIN users u ON u.id = c.parent_id
WHERE u.email IN (
    'demo.parent1@immunicare.local',
    'demo.parent2@immunicare.local'
);

UPDATE hospitals h
JOIN users u ON u.id = h.user_id
SET h.hospital_name = 'Demo Hospital Alpha',
    h.phone = '021-111-000-001',
    h.address = 'Demo Avenue, Karachi',
    h.city = 'Karachi',
    h.location = 'Clifton, Karachi',
    h.status = 'Active',
    h.verification_status = 'Approved'
WHERE u.email = 'demo.hospital.alpha@immunicare.local';

UPDATE hospitals h
JOIN users u ON u.id = h.user_id
SET h.hospital_name = 'Demo Hospital Beta',
    h.phone = '021-111-000-002',
    h.address = 'Demo Road, Karachi',
    h.city = 'Karachi',
    h.location = 'Gulshan, Karachi',
    h.status = 'Active',
    h.verification_status = 'Approved'
WHERE u.email = 'demo.hospital.beta@immunicare.local';

INSERT INTO hospitals
    (user_id, hospital_name, phone, address, city, location, status, verification_status)
SELECT u.id, 'Demo Hospital Alpha', '021-111-000-001',
       'Demo Avenue, Karachi', 'Karachi', 'Clifton, Karachi',
       'Active', 'Approved'
FROM users u
WHERE u.email = 'demo.hospital.alpha@immunicare.local'
  AND NOT EXISTS (
      SELECT 1 FROM hospitals h WHERE h.user_id = u.id
  );

INSERT INTO hospitals
    (user_id, hospital_name, phone, address, city, location, status, verification_status)
SELECT u.id, 'Demo Hospital Beta', '021-111-000-002',
       'Demo Road, Karachi', 'Karachi', 'Gulshan, Karachi',
       'Active', 'Approved'
FROM users u
WHERE u.email = 'demo.hospital.beta@immunicare.local'
  AND NOT EXISTS (
      SELECT 1 FROM hospitals h WHERE h.user_id = u.id
  );

INSERT INTO vaccines
    (vaccine_name, description, age_group, dose_number, availability)
SELECT 'MMR', 'Protects against measles, mumps, and rubella.',
       '12 Months - 6 Years', 2, 'Available'
WHERE NOT EXISTS (
    SELECT 1 FROM vaccines WHERE vaccine_name = 'MMR'
);

INSERT INTO vaccines
    (vaccine_name, description, age_group, dose_number, availability)
SELECT 'Seasonal Influenza', 'Annual protection against seasonal influenza.',
       '6 Months and older', 1, 'Available'
WHERE NOT EXISTS (
    SELECT 1 FROM vaccines WHERE vaccine_name = 'Seasonal Influenza'
);

INSERT INTO vaccine_doses
    (vaccine_id, dose_number, recommended_age_days, minimum_interval_days,
     clinical_source, status)
SELECT v.id, 1, 0, NULL, 'Demo national schedule', 'Active'
FROM vaccines v
WHERE v.vaccine_name IN ('BCG', 'Hepatitis B', 'Polio', 'MMR', 'Seasonal Influenza')
ON DUPLICATE KEY UPDATE
    status = 'Active',
    clinical_source = VALUES(clinical_source);

INSERT INTO vaccine_doses
    (vaccine_id, dose_number, recommended_age_days, minimum_interval_days,
     clinical_source, status)
SELECT v.id, 2, 42, 30, 'Demo national schedule', 'Active'
FROM vaccines v
WHERE v.vaccine_name IN ('Polio', 'MMR')
ON DUPLICATE KEY UPDATE
    recommended_age_days = VALUES(recommended_age_days),
    minimum_interval_days = 30,
    status = 'Active',
    clinical_source = VALUES(clinical_source);

INSERT INTO vaccine_doses
    (vaccine_id, dose_number, recommended_age_days, minimum_interval_days,
     clinical_source, status)
SELECT v.id, 3, 180, 90, 'Demo national schedule', 'Active'
FROM vaccines v
WHERE v.vaccine_name = 'Polio'
ON DUPLICATE KEY UPDATE
    recommended_age_days = VALUES(recommended_age_days),
    minimum_interval_days = VALUES(minimum_interval_days),
    status = 'Active',
    clinical_source = VALUES(clinical_source);

INSERT INTO children
    (parent_id, child_name, date_of_birth, gender, blood_group, address)
SELECT u.id, data.child_name, data.date_of_birth, data.gender,
       data.blood_group, data.address
FROM users u
JOIN (
    SELECT 'demo.parent1@immunicare.local' AS email,
           'Demo Fully Vaccinated Child' AS child_name,
           '2021-01-01' AS date_of_birth,
           'Female' AS gender, 'O+' AS blood_group,
           'Demo Family Address A' AS address
    UNION ALL
    SELECT 'demo.parent1@immunicare.local', 'Demo Next Dose Child',
           '2022-01-01', 'Male', 'B+', 'Demo Family Address A'
    UNION ALL
    SELECT 'demo.parent1@immunicare.local', 'Demo New Child',
           CURDATE(), 'Female', 'A+', 'Demo Family Address A'
    UNION ALL
    SELECT 'demo.parent1@immunicare.local', 'Demo Missed Child',
           '2020-06-01', 'Male', 'O+', 'Demo Family Address A'
    UNION ALL
    SELECT 'demo.parent1@immunicare.local', 'Demo Rejected Child',
           '2020-07-01', 'Female', 'AB+', 'Demo Family Address A'
    UNION ALL
    SELECT 'demo.parent2@immunicare.local', 'Demo Parent Two Child',
           '2023-03-01', 'Male', 'B-', 'Demo Family Address B'
) data ON data.email = u.email
WHERE NOT EXISTS (
    SELECT 1 FROM children c
    WHERE c.parent_id = u.id AND c.child_name = data.child_name
);

INSERT INTO hospital_inventory
    (hospital_id, vaccine_id, quantity, reorder_level)
SELECT h.id, v.id, 50, 10
FROM hospitals h
JOIN users u ON u.id = h.user_id
CROSS JOIN vaccines v
WHERE u.email IN (
    'demo.hospital.alpha@immunicare.local',
    'demo.hospital.beta@immunicare.local'
);

INSERT INTO inventory_transactions
    (hospital_id, vaccine_id, quantity_delta, quantity_after, reason)
SELECT i.hospital_id, i.vaccine_id, i.quantity, i.quantity,
       'DEMO opening balance'
FROM hospital_inventory i
JOIN hospitals h ON h.id = i.hospital_id
JOIN users u ON u.id = h.user_id
WHERE u.email IN (
    'demo.hospital.alpha@immunicare.local',
    'demo.hospital.beta@immunicare.local'
);

INSERT INTO hospital_slots
    (hospital_id, slot_date, slot_time, capacity, booked_count, status)
SELECT h.id, DATE_ADD(CURDATE(), INTERVAL 7 DAY), '10:00:00', 3, 0, 'Open'
FROM hospitals h
JOIN users u ON u.id = h.user_id
WHERE u.email = 'demo.hospital.alpha@immunicare.local';

INSERT INTO hospital_slots
    (hospital_id, slot_date, slot_time, capacity, booked_count, status)
SELECT h.id, DATE_ADD(CURDATE(), INTERVAL 14 DAY), '11:00:00', 2, 0, 'Open'
FROM hospitals h
JOIN users u ON u.id = h.user_id
WHERE u.email = 'demo.hospital.alpha@immunicare.local';

INSERT INTO hospital_slots
    (hospital_id, slot_date, slot_time, capacity, booked_count, status)
SELECT h.id, DATE_ADD(CURDATE(), INTERVAL 21 DAY), '14:00:00', 1, 0, 'Open'
FROM hospitals h
JOIN users u ON u.id = h.user_id
WHERE u.email = 'demo.hospital.beta@immunicare.local';

INSERT INTO hospital_slots
    (hospital_id, slot_date, slot_time, capacity, booked_count, status)
SELECT h.id, DATE_ADD(CURDATE(), INTERVAL 28 DAY), '15:00:00', 1, 0, 'Open'
FROM hospitals h
JOIN users u ON u.id = h.user_id
WHERE u.email = 'demo.hospital.beta@immunicare.local';

INSERT INTO hospital_slots
    (hospital_id, slot_date, slot_time, capacity, booked_count, status)
SELECT h.id, DATE_SUB(CURDATE(), INTERVAL 200 DAY), '10:00:00', 1, 1, 'Closed'
FROM hospitals h
JOIN users u ON u.id = h.user_id
WHERE u.email = 'demo.hospital.alpha@immunicare.local';

INSERT INTO hospital_slots
    (hospital_id, slot_date, slot_time, capacity, booked_count, status)
SELECT h.id, DATE_SUB(CURDATE(), INTERVAL 100 DAY), '10:00:00', 1, 1, 'Closed'
FROM hospitals h
JOIN users u ON u.id = h.user_id
WHERE u.email = 'demo.hospital.alpha@immunicare.local';

INSERT INTO hospital_slots
    (hospital_id, slot_date, slot_time, capacity, booked_count, status)
SELECT h.id, DATE_SUB(CURDATE(), INTERVAL 10 DAY), '10:00:00', 1, 1, 'Closed'
FROM hospitals h
JOIN users u ON u.id = h.user_id
WHERE u.email = 'demo.hospital.alpha@immunicare.local';

INSERT INTO hospital_slots
    (hospital_id, slot_date, slot_time, capacity, booked_count, status)
SELECT h.id, DATE_SUB(CURDATE(), INTERVAL 40 DAY), '11:00:00', 1, 1, 'Closed'
FROM hospitals h
JOIN users u ON u.id = h.user_id
WHERE u.email = 'demo.hospital.alpha@immunicare.local';

INSERT INTO hospital_slots
    (hospital_id, slot_date, slot_time, capacity, booked_count, status)
SELECT h.id, DATE_SUB(CURDATE(), INTERVAL 7 DAY), '14:00:00', 1, 1, 'Closed'
FROM hospitals h
JOIN users u ON u.id = h.user_id
WHERE u.email = 'demo.hospital.beta@immunicare.local';

INSERT INTO bookings
    (parent_id, child_id, hospital_id, vaccine_id, vaccine_dose_id,
     slot_id, booking_date, booking_time, status)
SELECT p.id, c.id, h.id, v.id, d.id, s.id,
       s.slot_date, s.slot_time, 'Completed'
FROM users p
JOIN children c ON c.parent_id = p.id
JOIN hospitals h ON h.hospital_name = 'Demo Hospital Alpha'
JOIN vaccines v ON v.vaccine_name = 'Polio'
JOIN vaccine_doses d ON d.vaccine_id = v.id AND d.dose_number = 1
JOIN hospital_slots s ON s.hospital_id = h.id
    AND s.slot_date = DATE_SUB(CURDATE(), INTERVAL 200 DAY)
    AND s.slot_time = '10:00:00'
WHERE p.email = 'demo.parent1@immunicare.local'
  AND c.child_name = 'Demo Fully Vaccinated Child';

INSERT INTO bookings
    (parent_id, child_id, hospital_id, vaccine_id, vaccine_dose_id,
     slot_id, booking_date, booking_time, status)
SELECT p.id, c.id, h.id, v.id, d.id, s.id,
       s.slot_date, s.slot_time, 'Completed'
FROM users p
JOIN children c ON c.parent_id = p.id
JOIN hospitals h ON h.hospital_name = 'Demo Hospital Alpha'
JOIN vaccines v ON v.vaccine_name = 'Polio'
JOIN vaccine_doses d ON d.vaccine_id = v.id AND d.dose_number = 2
JOIN hospital_slots s ON s.hospital_id = h.id
    AND s.slot_date = DATE_SUB(CURDATE(), INTERVAL 100 DAY)
    AND s.slot_time = '10:00:00'
WHERE p.email = 'demo.parent1@immunicare.local'
  AND c.child_name = 'Demo Fully Vaccinated Child';

INSERT INTO bookings
    (parent_id, child_id, hospital_id, vaccine_id, vaccine_dose_id,
     slot_id, booking_date, booking_time, status)
SELECT p.id, c.id, h.id, v.id, d.id, s.id,
       s.slot_date, s.slot_time, 'Completed'
FROM users p
JOIN children c ON c.parent_id = p.id
JOIN hospitals h ON h.hospital_name = 'Demo Hospital Alpha'
JOIN vaccines v ON v.vaccine_name = 'Polio'
JOIN vaccine_doses d ON d.vaccine_id = v.id AND d.dose_number = 3
JOIN hospital_slots s ON s.hospital_id = h.id
    AND s.slot_date = DATE_SUB(CURDATE(), INTERVAL 10 DAY)
    AND s.slot_time = '10:00:00'
WHERE p.email = 'demo.parent1@immunicare.local'
  AND c.child_name = 'Demo Fully Vaccinated Child';

INSERT INTO bookings
    (parent_id, child_id, hospital_id, vaccine_id, vaccine_dose_id,
     slot_id, booking_date, booking_time, status)
SELECT p.id, c.id, h.id, v.id, d.id, s.id,
       s.slot_date, s.slot_time, 'Approved'
FROM users p
JOIN children c ON c.parent_id = p.id
JOIN hospitals h ON h.hospital_name = 'Demo Hospital Alpha'
JOIN vaccines v ON v.vaccine_name = 'Polio'
JOIN vaccine_doses d ON d.vaccine_id = v.id AND d.dose_number = 2
JOIN hospital_slots s ON s.hospital_id = h.id
    AND s.slot_date = DATE_ADD(CURDATE(), INTERVAL 14 DAY)
    AND s.slot_time = '11:00:00'
WHERE p.email = 'demo.parent1@immunicare.local'
  AND c.child_name = 'Demo Next Dose Child';

INSERT INTO bookings
    (parent_id, child_id, hospital_id, vaccine_id, vaccine_dose_id,
     slot_id, booking_date, booking_time, status)
SELECT p.id, c.id, h.id, v.id, d.id, s.id,
       s.slot_date, s.slot_time, 'Completed'
FROM users p
JOIN children c ON c.parent_id = p.id
JOIN hospitals h ON h.hospital_name = 'Demo Hospital Alpha'
JOIN vaccines v ON v.vaccine_name = 'Polio'
JOIN vaccine_doses d ON d.vaccine_id = v.id AND d.dose_number = 1
JOIN hospital_slots s ON s.hospital_id = h.id
    AND s.slot_date = DATE_SUB(CURDATE(), INTERVAL 40 DAY)
    AND s.slot_time = '11:00:00'
WHERE p.email = 'demo.parent1@immunicare.local'
  AND c.child_name = 'Demo Next Dose Child';

INSERT INTO bookings
    (parent_id, child_id, hospital_id, vaccine_id, vaccine_dose_id,
     slot_id, booking_date, booking_time, status)
SELECT p.id, c.id, h.id, v.id, d.id, s.id,
       s.slot_date, s.slot_time, 'Pending'
FROM users p
JOIN children c ON c.parent_id = p.id
JOIN hospitals h ON h.hospital_name = 'Demo Hospital Alpha'
JOIN vaccines v ON v.vaccine_name = 'BCG'
JOIN vaccine_doses d ON d.vaccine_id = v.id AND d.dose_number = 1
JOIN hospital_slots s ON s.hospital_id = h.id
    AND s.slot_date = DATE_ADD(CURDATE(), INTERVAL 7 DAY)
    AND s.slot_time = '10:00:00'
WHERE p.email = 'demo.parent1@immunicare.local'
  AND c.child_name = 'Demo New Child';

INSERT INTO bookings
    (parent_id, child_id, hospital_id, vaccine_id, vaccine_dose_id,
     slot_id, booking_date, booking_time, status)
SELECT p.id, c.id, h.id, v.id, d.id, s.id,
       s.slot_date, s.slot_time, 'Pending'
FROM users p
JOIN children c ON c.parent_id = p.id
JOIN hospitals h ON h.hospital_name = 'Demo Hospital Beta'
JOIN vaccines v ON v.vaccine_name = 'MMR'
JOIN vaccine_doses d ON d.vaccine_id = v.id AND d.dose_number = 1
JOIN hospital_slots s ON s.hospital_id = h.id
    AND s.slot_date = DATE_ADD(CURDATE(), INTERVAL 21 DAY)
    AND s.slot_time = '14:00:00'
WHERE p.email = 'demo.parent2@immunicare.local'
  AND c.child_name = 'Demo Parent Two Child';

INSERT INTO bookings
    (parent_id, child_id, hospital_id, vaccine_id, vaccine_dose_id,
     slot_id, booking_date, booking_time, status)
SELECT p.id, c.id, h.id, v.id, d.id, s.id,
       s.slot_date, s.slot_time, 'Rejected'
FROM users p
JOIN children c ON c.parent_id = p.id
JOIN hospitals h ON h.hospital_name = 'Demo Hospital Beta'
JOIN vaccines v ON v.vaccine_name = 'MMR'
JOIN vaccine_doses d ON d.vaccine_id = v.id AND d.dose_number = 1
JOIN hospital_slots s ON s.hospital_id = h.id
    AND s.slot_date = DATE_ADD(CURDATE(), INTERVAL 28 DAY)
    AND s.slot_time = '15:00:00'
WHERE p.email = 'demo.parent1@immunicare.local'
  AND c.child_name = 'Demo Rejected Child';

INSERT INTO bookings
    (parent_id, child_id, hospital_id, vaccine_id, vaccine_dose_id,
     slot_id, booking_date, booking_time, status)
SELECT p.id, c.id, h.id, v.id, d.id, s.id,
       s.slot_date, s.slot_time, 'Cancelled'
FROM users p
JOIN children c ON c.parent_id = p.id
JOIN hospitals h ON h.hospital_name = 'Demo Hospital Beta'
JOIN vaccines v ON v.vaccine_name = 'MMR'
JOIN vaccine_doses d ON d.vaccine_id = v.id AND d.dose_number = 1
JOIN hospital_slots s ON s.hospital_id = h.id
    AND s.slot_date = DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    AND s.slot_time = '14:00:00'
WHERE p.email = 'demo.parent1@immunicare.local'
  AND c.child_name = 'Demo Missed Child';

INSERT INTO vaccination_schedules
    (booking_id, child_id, vaccine_id, vaccine_dose_id, hospital_id,
     dose_number, scheduled_date, scheduled_time, status)
SELECT b.id, b.child_id, b.vaccine_id, b.vaccine_dose_id, b.hospital_id,
       d.dose_number, b.booking_date, b.booking_time, 'Completed'
FROM bookings b
JOIN vaccine_doses d ON d.id = b.vaccine_dose_id
JOIN children c ON c.id = b.child_id
WHERE c.child_name = 'Demo Fully Vaccinated Child'
  AND b.status = 'Completed';

INSERT INTO vaccination_schedules
    (booking_id, child_id, vaccine_id, vaccine_dose_id, hospital_id,
     dose_number, scheduled_date, scheduled_time, status)
SELECT b.id, b.child_id, b.vaccine_id, b.vaccine_dose_id, b.hospital_id,
       d.dose_number, b.booking_date, b.booking_time, 'Completed'
FROM bookings b
JOIN vaccine_doses d ON d.id = b.vaccine_dose_id
JOIN children c ON c.id = b.child_id
WHERE c.child_name = 'Demo Next Dose Child'
  AND b.status = 'Completed';

INSERT INTO vaccination_schedules
    (booking_id, child_id, vaccine_id, vaccine_dose_id, hospital_id,
     dose_number, scheduled_date, scheduled_time, status)
SELECT b.id, b.child_id, b.vaccine_id, b.vaccine_dose_id, b.hospital_id,
       d.dose_number, b.booking_date, b.booking_time, 'Scheduled'
FROM bookings b
JOIN vaccine_doses d ON d.id = b.vaccine_dose_id
JOIN children c ON c.id = b.child_id
WHERE c.child_name = 'Demo Next Dose Child'
  AND b.status = 'Approved';

INSERT INTO vaccination_schedules
    (booking_id, child_id, vaccine_id, vaccine_dose_id, hospital_id,
     dose_number, scheduled_date, scheduled_time, status)
SELECT b.id, b.child_id, b.vaccine_id, b.vaccine_dose_id, b.hospital_id,
       d.dose_number, b.booking_date, b.booking_time, 'Missed'
FROM bookings b
JOIN vaccine_doses d ON d.id = b.vaccine_dose_id
JOIN children c ON c.id = b.child_id
WHERE c.child_name = 'Demo Missed Child'
  AND b.status = 'Cancelled';

INSERT INTO vaccination_records
    (booking_id, schedule_id, child_id, vaccine_id, vaccine_dose_id,
     hospital_id, dose_number, vaccination_date, status, remarks, recorded_by)
SELECT b.id, s.id, b.child_id, b.vaccine_id, b.vaccine_dose_id,
       b.hospital_id, d.dose_number, b.booking_date, 'Vaccinated',
       CONCAT('DEMO: completed vaccination dose ', d.dose_number), a.id
FROM bookings b
JOIN vaccination_schedules s ON s.booking_id = b.id
JOIN vaccine_doses d ON d.id = b.vaccine_dose_id
JOIN children c ON c.id = b.child_id
JOIN users a ON a.email = 'demo.admin@immunicare.local'
WHERE c.child_name = 'Demo Fully Vaccinated Child'
  AND b.status = 'Completed';

INSERT INTO vaccination_records
    (booking_id, schedule_id, child_id, vaccine_id, vaccine_dose_id,
     hospital_id, dose_number, vaccination_date, status, remarks, recorded_by)
SELECT b.id, s.id, b.child_id, b.vaccine_id, b.vaccine_dose_id,
       b.hospital_id, d.dose_number, b.booking_date, 'Vaccinated',
       'DEMO: previous dose completed; next dose is now due', a.id
FROM bookings b
JOIN vaccination_schedules s ON s.booking_id = b.id
JOIN vaccine_doses d ON d.id = b.vaccine_dose_id
JOIN children c ON c.id = b.child_id
JOIN users a ON a.email = 'demo.admin@immunicare.local'
WHERE c.child_name = 'Demo Next Dose Child'
  AND b.status = 'Completed';

INSERT INTO vaccination_records
    (booking_id, schedule_id, child_id, vaccine_id, vaccine_dose_id,
     hospital_id, dose_number, vaccination_date, status, remarks, recorded_by)
SELECT b.id, s.id, b.child_id, b.vaccine_id, b.vaccine_dose_id,
       b.hospital_id, d.dose_number, b.booking_date, 'Not Vaccinated',
       'DEMO: appointment missed by child', a.id
FROM bookings b
JOIN vaccination_schedules s ON s.booking_id = b.id
JOIN vaccine_doses d ON d.id = b.vaccine_dose_id
JOIN children c ON c.id = b.child_id
JOIN users a ON a.email = 'demo.admin@immunicare.local'
WHERE c.child_name = 'Demo Missed Child'
  AND b.status = 'Cancelled';

UPDATE hospital_inventory i
JOIN hospitals h ON h.id = i.hospital_id
JOIN users u ON u.id = h.user_id
JOIN vaccines v ON v.id = i.vaccine_id
SET i.quantity = 46
WHERE u.email = 'demo.hospital.alpha@immunicare.local'
  AND v.vaccine_name = 'Polio';

INSERT INTO inventory_transactions
    (hospital_id, vaccine_id, booking_id, vaccination_record_id,
     actor_user_id, quantity_delta, quantity_after, reason)
SELECT b.hospital_id, b.vaccine_id, b.id, r.id, a.id,
       -1,
       CASE
           WHEN c.child_name = 'Demo Fully Vaccinated Child'
               THEN 50 - d.dose_number
           ELSE 46
       END,
       'DEMO vaccination administered'
FROM vaccination_records r
JOIN bookings b ON b.id = r.booking_id
JOIN vaccine_doses d ON d.id = r.vaccine_dose_id
JOIN users a ON a.email = 'demo.admin@immunicare.local'
JOIN children c ON c.id = r.child_id
WHERE c.child_name IN ('Demo Fully Vaccinated Child', 'Demo Next Dose Child')
  AND r.status = 'Vaccinated';

INSERT INTO notifications
    (user_id, title, message, type, is_read, link_url, read_at)
SELECT u.id, 'Demo pending appointment',
       'A demo appointment is waiting for hospital approval.',
       'appointment', 0, 'Parent/bookings.php', NULL
FROM users u
WHERE u.email = 'demo.parent1@immunicare.local';

INSERT INTO notifications
    (user_id, title, message, type, is_read, link_url, read_at)
SELECT u.id, 'Demo inventory reminder',
       'Review the demo hospital inventory and reorder thresholds.',
       'inventory', 0, 'Admin/inventory.php', NULL
FROM users u
WHERE u.email = 'demo.admin@immunicare.local';

INSERT INTO notifications
    (user_id, title, message, type, is_read, link_url, read_at)
SELECT u.id, 'Demo appointment approved',
       'A demo appointment has been approved and scheduled.',
       'appointment', 1, 'Parent/schedule.php', NOW()
FROM users u
WHERE u.email = 'demo.parent1@immunicare.local';

INSERT INTO notification_outbox
    (user_id, channel, event_type, payload, status)
SELECT u.id, 'email', 'demo_report',
       JSON_OBJECT('message', 'Demo outbox event for local testing'),
       'Pending'
FROM users u
WHERE u.email = 'demo.admin@immunicare.local';

INSERT INTO audit_logs
    (actor_user_id, action, entity_type, entity_id, details)
SELECT u.id, 'demo.seeded', 'demo', NULL,
       JSON_OBJECT('scenario', 'dose-inventory-reporting')
FROM users u
WHERE u.email = 'demo.admin@immunicare.local';

INSERT INTO report_exports
    (requested_by, report_type, format, filters, status)
SELECT u.id, 'inventory', 'CSV',
       JSON_OBJECT('demo', TRUE), 'Ready'
FROM users u
WHERE u.email = 'demo.admin@immunicare.local';

INSERT IGNORE INTO schema_migrations (version)
VALUES ('005_demo_scenario_data');

COMMIT;
