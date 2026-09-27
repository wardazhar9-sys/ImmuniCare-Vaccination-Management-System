-- Domain expansion migration. Apply once after 001_harden_schema.sql.

CREATE TABLE IF NOT EXISTS schema_migrations (
    version VARCHAR(40) NOT NULL,
    applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (version)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS vaccine_doses (
    id INT NOT NULL AUTO_INCREMENT,
    vaccine_id INT NOT NULL,
    dose_number INT NOT NULL,
    recommended_age_days INT NULL,
    minimum_interval_days INT NULL,
    catch_up_rule VARCHAR(500) NULL,
    clinical_source VARCHAR(255) NULL,
    source_version VARCHAR(50) NULL,
    status ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_vaccine_dose (vaccine_id, dose_number),
    KEY idx_dose_vaccine_status (vaccine_id, status),
    CONSTRAINT fk_dose_vaccine
        FOREIGN KEY (vaccine_id) REFERENCES vaccines(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO vaccine_doses
    (vaccine_id, dose_number, status)
SELECT id, dose_number, 'Active'
FROM vaccines;

ALTER TABLE hospitals
    ADD COLUMN IF NOT EXISTS verification_status
        ENUM('Pending', 'Approved', 'Rejected') NOT NULL DEFAULT 'Pending' AFTER status;

UPDATE hospitals
SET verification_status = 'Approved'
WHERE status = 'Active' AND verification_status = 'Pending';

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS email_verified_at DATETIME NULL AFTER status;

ALTER TABLE bookings
    ADD COLUMN IF NOT EXISTS vaccine_dose_id INT NULL AFTER vaccine_id,
    ADD COLUMN IF NOT EXISTS rejection_reason VARCHAR(500) NULL AFTER cancelled_at,
    ADD COLUMN IF NOT EXISTS rescheduled_at DATETIME NULL AFTER rejection_reason;

ALTER TABLE vaccination_schedules
    ADD COLUMN IF NOT EXISTS vaccine_dose_id INT NULL AFTER vaccine_id,
    ADD COLUMN IF NOT EXISTS scheduled_at_utc DATETIME NULL AFTER scheduled_time;

ALTER TABLE vaccination_records
    ADD COLUMN IF NOT EXISTS vaccine_dose_id INT NULL AFTER vaccine_id,
    ADD COLUMN IF NOT EXISTS lot_number VARCHAR(100) NULL AFTER remarks,
    ADD COLUMN IF NOT EXISTS manufacturer VARCHAR(150) NULL AFTER lot_number,
    ADD COLUMN IF NOT EXISTS expiry_date DATE NULL AFTER manufacturer,
    ADD COLUMN IF NOT EXISTS administration_site VARCHAR(100) NULL AFTER expiry_date,
    ADD COLUMN IF NOT EXISTS recorded_at DATETIME NULL AFTER vaccination_date;

UPDATE bookings b
JOIN vaccine_doses d
    ON d.vaccine_id = b.vaccine_id
    AND d.dose_number = (SELECT dose_number FROM vaccines WHERE id = b.vaccine_id)
SET b.vaccine_dose_id = d.id
WHERE b.vaccine_dose_id IS NULL;

UPDATE vaccination_schedules s
JOIN vaccine_doses d
    ON d.vaccine_id = s.vaccine_id
    AND d.dose_number = s.dose_number
SET s.vaccine_dose_id = d.id
WHERE s.vaccine_dose_id IS NULL;

UPDATE vaccination_records r
JOIN vaccine_doses d
    ON d.vaccine_id = r.vaccine_id
    AND d.dose_number = r.dose_number
SET r.vaccine_dose_id = d.id,
    r.recorded_at = COALESCE(r.recorded_at, r.created_at)
WHERE r.vaccine_dose_id IS NULL;

ALTER TABLE notifications
    ADD COLUMN IF NOT EXISTS booking_id INT NULL AFTER user_id,
    ADD COLUMN IF NOT EXISTS schedule_id INT NULL AFTER booking_id,
    ADD COLUMN IF NOT EXISTS record_id INT NULL AFTER schedule_id,
    ADD COLUMN IF NOT EXISTS read_at DATETIME NULL AFTER is_read;

CREATE TABLE IF NOT EXISTS hospital_slots (
    id INT NOT NULL AUTO_INCREMENT,
    hospital_id INT NOT NULL,
    slot_date DATE NOT NULL,
    slot_time TIME NOT NULL,
    capacity INT NOT NULL DEFAULT 1,
    booked_count INT NOT NULL DEFAULT 0,
    status ENUM('Open', 'Closed') NOT NULL DEFAULT 'Open',
    PRIMARY KEY (id),
    UNIQUE KEY uq_hospital_slot (hospital_id, slot_date, slot_time),
    KEY idx_slot_availability (hospital_id, slot_date, status),
    CONSTRAINT fk_slot_hospital
        FOREIGN KEY (hospital_id) REFERENCES hospitals(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS hospital_inventory (
    id INT NOT NULL AUTO_INCREMENT,
    hospital_id INT NOT NULL,
    vaccine_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 0,
    reorder_level INT NOT NULL DEFAULT 0,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_hospital_inventory (hospital_id, vaccine_id),
    CONSTRAINT fk_inventory_hospital
        FOREIGN KEY (hospital_id) REFERENCES hospitals(id),
    CONSTRAINT fk_inventory_vaccine
        FOREIGN KEY (vaccine_id) REFERENCES vaccines(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS notification_outbox (
    id BIGINT NOT NULL AUTO_INCREMENT,
    user_id INT NOT NULL,
    channel ENUM('in_app', 'email', 'sms', 'whatsapp') NOT NULL DEFAULT 'in_app',
    event_type VARCHAR(100) NOT NULL,
    payload JSON NOT NULL,
    status ENUM('Pending', 'Sent', 'Failed') NOT NULL DEFAULT 'Pending',
    attempts INT NOT NULL DEFAULT 0,
    last_error VARCHAR(500) NULL,
    available_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    sent_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_outbox_status_available (status, available_at),
    CONSTRAINT fk_outbox_user
        FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS email_verification_tokens (
    id BIGINT NOT NULL AUTO_INCREMENT,
    user_id INT NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_email_token (token_hash),
    KEY idx_email_token_user (user_id, expires_at),
    CONSTRAINT fk_email_token_user
        FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id BIGINT NOT NULL AUTO_INCREMENT,
    user_id INT NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_reset_token (token_hash),
    KEY idx_reset_token_user (user_id, expires_at),
    CONSTRAINT fk_reset_token_user
        FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS api_tokens (
    id BIGINT NOT NULL AUTO_INCREMENT,
    user_id INT NOT NULL,
    token_hash CHAR(64) NOT NULL,
    name VARCHAR(100) NOT NULL,
    expires_at DATETIME NULL,
    revoked_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_api_token_hash (token_hash),
    KEY idx_api_token_user (user_id, revoked_at),
    CONSTRAINT fk_api_token_user
        FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO schema_migrations (version)
VALUES ('002_domain_expansion');
