-- Replace technical demo labels with realistic Karachi sample identities.

START TRANSACTION;

UPDATE users
SET name = 'Sana Ahmed',
    email = 'sana.ahmed@immunicare.local'
WHERE email = 'demo.admin@immunicare.local';

UPDATE users
SET name = 'Ayesha Khan',
    email = 'ayesha.khan@immunicare.local'
WHERE email = 'demo.parent1@immunicare.local';

UPDATE users
SET name = 'Muhammad Ali',
    email = 'muhammad.ali@immunicare.local'
WHERE email = 'demo.parent2@immunicare.local';

UPDATE users
SET name = 'Aga Khan Hospital Account',
    email = 'aga.khan@immunicare.local'
WHERE email = 'demo.hospital.alpha@immunicare.local';

UPDATE users
SET name = 'Liaquat National Hospital Account',
    email = 'liaquat.national@immunicare.local'
WHERE email = 'demo.hospital.beta@immunicare.local';

UPDATE hospitals h
JOIN users u ON u.id = h.user_id
SET h.hospital_name = 'Aga Khan University Hospital',
    h.phone = '021-111-911-911',
    h.address = 'Stadium Road, Karachi',
    h.city = 'Karachi',
    h.location = 'Gulshan-e-Iqbal, Karachi'
WHERE u.email = 'aga.khan@immunicare.local';

UPDATE hospitals h
JOIN users u ON u.id = h.user_id
SET h.hospital_name = 'Liaquat National Hospital',
    h.phone = '021-111-456-456',
    h.address = 'National Stadium Road, Karachi',
    h.city = 'Karachi',
    h.location = 'Gulshan-e-Iqbal, Karachi'
WHERE u.email = 'liaquat.national@immunicare.local';

UPDATE children c
JOIN users u ON u.id = c.parent_id
SET c.child_name = CASE c.child_name
    WHEN 'Demo Fully Vaccinated Child' THEN 'Maryam Khan'
    WHEN 'Demo Next Dose Child' THEN 'Hamza Khan'
    WHEN 'Demo New Child' THEN 'Eman Khan'
    WHEN 'Demo Missed Child' THEN 'Zain Khan'
    WHEN 'Demo Rejected Child' THEN 'Hira Khan'
    ELSE c.child_name
END
WHERE u.email = 'ayesha.khan@immunicare.local';

UPDATE children c
JOIN users u ON u.id = c.parent_id
SET c.child_name = 'Usman Ali'
WHERE u.email = 'muhammad.ali@immunicare.local'
  AND c.child_name = 'Demo Parent Two Child';

UPDATE vaccination_records
SET remarks = CASE
    WHEN remarks LIKE 'DEMO: completed vaccination dose %'
        THEN REPLACE(remarks, 'DEMO: ', '')
    WHEN remarks = 'DEMO: previous dose completed; next dose is now due'
        THEN 'Previous dose completed; next dose is now due'
    WHEN remarks = 'DEMO: appointment missed by child'
        THEN 'Appointment missed by child'
    ELSE remarks
END
WHERE remarks LIKE 'DEMO:%';

UPDATE inventory_transactions
SET reason = CASE reason
    WHEN 'DEMO opening balance' THEN 'Opening balance'
    WHEN 'DEMO vaccination administered' THEN 'Vaccination administered'
    ELSE reason
END
WHERE reason LIKE 'DEMO%';

UPDATE notifications
SET title = REPLACE(title, 'Demo ', ''),
    message = REPLACE(message, 'A demo ', 'A ')
WHERE title LIKE 'Demo %' OR message LIKE '%demo%';

UPDATE notification_outbox
SET event_type = 'report_export',
    payload = JSON_OBJECT('message', 'Local outbox event for testing')
WHERE event_type = 'demo_report';

UPDATE audit_logs
SET action = 'scenario.seeded',
    details = JSON_OBJECT('scenario', 'Karachi vaccination workflow')
WHERE action = 'demo.seeded';

UPDATE report_exports
SET filters = JSON_OBJECT('scenario', 'Karachi')
WHERE JSON_UNQUOTE(JSON_EXTRACT(filters, '$.demo')) = 'true';

INSERT IGNORE INTO schema_migrations (version)
VALUES ('006_karachi_sample_names');

COMMIT;
