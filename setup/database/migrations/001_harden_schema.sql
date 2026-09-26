-- Run after vaccination_management_system(1).sql.
-- MariaDB/MySQLi only.

CREATE TABLE IF NOT EXISTS schema_migrations (
    version VARCHAR(40) NOT NULL,
    applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (version)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS status ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active' AFTER role;

ALTER TABLE hospitals
    MODIFY status ENUM('Pending', 'Active', 'Inactive') NOT NULL DEFAULT 'Pending',
    ADD COLUMN IF NOT EXISTS verified_at DATETIME NULL AFTER status;

ALTER TABLE children
    ADD COLUMN IF NOT EXISTS archived_at DATETIME NULL AFTER created_at;

ALTER TABLE bookings
    ADD COLUMN IF NOT EXISTS notes VARCHAR(500) NULL AFTER booking_time,
    ADD COLUMN IF NOT EXISTS cancelled_at DATETIME NULL AFTER status,
    ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP AFTER created_at;

ALTER TABLE vaccination_schedules
    ADD COLUMN IF NOT EXISTS booking_id INT NULL AFTER id,
    ADD COLUMN IF NOT EXISTS hospital_id INT NULL AFTER vaccine_id,
    ADD COLUMN IF NOT EXISTS dose_number INT NOT NULL DEFAULT 1 AFTER vaccine_id,
    ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP AFTER created_at;

ALTER TABLE vaccination_records
    ADD COLUMN IF NOT EXISTS booking_id INT NULL AFTER id,
    ADD COLUMN IF NOT EXISTS schedule_id INT NULL AFTER booking_id,
    ADD COLUMN IF NOT EXISTS dose_number INT NOT NULL DEFAULT 1 AFTER vaccine_id,
    ADD COLUMN IF NOT EXISTS recorded_by INT NULL AFTER hospital_id,
    ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP AFTER created_at;

ALTER TABLE notifications
    ADD COLUMN IF NOT EXISTS link_url VARCHAR(255) NULL AFTER type,
    MODIFY is_read TINYINT(1) NOT NULL DEFAULT 0;

UPDATE hospitals
SET status = 'Active'
WHERE status IS NULL OR status = '';

UPDATE vaccination_schedules vs
JOIN (
    SELECT child_id, vaccine_id, MIN(id) AS booking_id
    FROM bookings
    GROUP BY child_id, vaccine_id
) b ON b.child_id = vs.child_id AND b.vaccine_id = vs.vaccine_id
JOIN bookings source_booking ON source_booking.id = b.booking_id
JOIN vaccines v ON v.id = vs.vaccine_id
SET
    vs.booking_id = b.booking_id,
    vs.hospital_id = source_booking.hospital_id,
    vs.dose_number = v.dose_number
WHERE vs.booking_id IS NULL;

UPDATE vaccination_records vr
JOIN (
    SELECT child_id, vaccine_id, hospital_id, MIN(id) AS booking_id
    FROM bookings
    GROUP BY child_id, vaccine_id, hospital_id
) b ON b.child_id = vr.child_id
    AND b.vaccine_id = vr.vaccine_id
    AND b.hospital_id = vr.hospital_id
JOIN vaccines v ON v.id = vr.vaccine_id
SET
    vr.booking_id = b.booking_id,
    vr.dose_number = v.dose_number
WHERE vr.booking_id IS NULL;

UPDATE vaccination_records vr
JOIN vaccination_schedules vs
    ON vs.child_id = vr.child_id
    AND vs.vaccine_id = vr.vaccine_id
    AND vs.hospital_id = vr.hospital_id
SET vr.schedule_id = vs.id
WHERE vr.schedule_id IS NULL;

ALTER TABLE hospitals
    ADD UNIQUE KEY uq_hospitals_user_id (user_id),
    ADD KEY idx_hospitals_status_city (status, city);

ALTER TABLE bookings
    ADD KEY idx_bookings_parent_status (parent_id, status),
    ADD KEY idx_bookings_hospital_date (hospital_id, booking_date, booking_time),
    ADD KEY idx_bookings_child_status (child_id, status);

ALTER TABLE children
    ADD KEY idx_children_parent_archived (parent_id, archived_at);

ALTER TABLE vaccination_schedules
    ADD UNIQUE KEY uq_schedule_booking (booking_id),
    ADD KEY idx_schedule_hospital_date (hospital_id, scheduled_date, scheduled_time),
    ADD KEY idx_schedule_child_status (child_id, status);

ALTER TABLE vaccination_records
    ADD UNIQUE KEY uq_record_booking (booking_id),
    ADD KEY idx_records_child_date (child_id, vaccination_date),
    ADD KEY idx_records_hospital_date (hospital_id, vaccination_date);

ALTER TABLE notifications
    ADD KEY idx_notifications_user_read (user_id, is_read, created_at);

ALTER TABLE vaccination_schedules
    ADD CONSTRAINT fk_schedule_booking
        FOREIGN KEY (booking_id) REFERENCES bookings(id),
    ADD CONSTRAINT fk_schedule_hospital
        FOREIGN KEY (hospital_id) REFERENCES hospitals(id);

ALTER TABLE vaccination_records
    ADD CONSTRAINT fk_record_booking
        FOREIGN KEY (booking_id) REFERENCES bookings(id),
    ADD CONSTRAINT fk_record_schedule
        FOREIGN KEY (schedule_id) REFERENCES vaccination_schedules(id),
    ADD CONSTRAINT fk_record_user
        FOREIGN KEY (recorded_by) REFERENCES users(id);

ALTER TABLE notifications
    ADD CONSTRAINT fk_notifications_user
        FOREIGN KEY (user_id) REFERENCES users(id);

CREATE TABLE IF NOT EXISTS contact_messages (
    id INT NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    subject VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('New', 'In Progress', 'Resolved', 'Spam') NOT NULL DEFAULT 'New',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_contact_status_created (status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS audit_logs (
    id BIGINT NOT NULL AUTO_INCREMENT,
    actor_user_id INT NULL,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(60) NOT NULL,
    entity_id INT NULL,
    details JSON NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_audit_entity (entity_type, entity_id, created_at),
    KEY idx_audit_actor (actor_user_id, created_at),
    CONSTRAINT fk_audit_actor
        FOREIGN KEY (actor_user_id) REFERENCES users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO schema_migrations (version)
VALUES ('001_harden_schema');
