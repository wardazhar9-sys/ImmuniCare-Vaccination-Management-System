INSERT IGNORE INTO users
    (name, email, password, role, status, email_verified_at)
VALUES
    ('Test Parent', 'parent@test.local', '$2y$12$EHQ9dvv951pE1iqa6TQAwO81i.1ESJdaEhUd1hSHhITI/pLViLXWq', 'parent', 'Active', NOW()),
    ('Test Hospital', 'hospital@test.local', '$2y$12$EHQ9dvv951pE1iqa6TQAwO81i.1ESJdaEhUd1hSHhITI/pLViLXWq', 'hospital', 'Active', NOW()),
    ('Test Admin', 'admin@test.local', '$2y$12$EHQ9dvv951pE1iqa6TQAwO81i.1ESJdaEhUd1hSHhITI/pLViLXWq', 'admin', 'Active', NOW());

INSERT INTO hospitals
    (user_id, hospital_name, phone, address, city, location, status, verification_status)
SELECT id, 'Test Hospital', '000-0000', 'Test Address', 'Test City', 'Test Location', 'Active', 'Approved'
FROM users
WHERE email = 'hospital@test.local'
  AND NOT EXISTS (
      SELECT 1 FROM hospitals h
      WHERE h.user_id = users.id
  );

INSERT INTO children
    (parent_id, child_name, date_of_birth, gender, blood_group, address)
SELECT id, 'Test Child', '2022-01-01', 'Female', 'O+', 'Test Address'
FROM users
WHERE email = 'parent@test.local'
  AND NOT EXISTS (
      SELECT 1 FROM children c
      WHERE c.parent_id = users.id AND c.child_name = 'Test Child'
  );
