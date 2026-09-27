-- Complete dose relationships and inventory auditability.

UPDATE bookings b
JOIN vaccine_doses d
    ON d.vaccine_id = b.vaccine_id
   AND d.dose_number = (
       SELECT v.dose_number
       FROM vaccines v
       WHERE v.id = b.vaccine_id
   )
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
SET r.vaccine_dose_id = d.id
WHERE r.vaccine_dose_id IS NULL;

UPDATE bookings b
SET b.vaccine_dose_id = NULL
WHERE b.vaccine_dose_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM vaccine_doses d WHERE d.id = b.vaccine_dose_id
  );

UPDATE vaccination_schedules s
SET s.vaccine_dose_id = NULL
WHERE s.vaccine_dose_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM vaccine_doses d WHERE d.id = s.vaccine_dose_id
  );

UPDATE vaccination_records r
SET r.vaccine_dose_id = NULL
WHERE r.vaccine_dose_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM vaccine_doses d WHERE d.id = r.vaccine_dose_id
  );

UPDATE hospital_slots
SET capacity = 1
WHERE capacity <= 0;

UPDATE hospital_slots
SET booked_count = capacity
WHERE booked_count > capacity;

UPDATE hospital_inventory
SET quantity = 0
WHERE quantity < 0;

UPDATE hospital_inventory
SET reorder_level = 0
WHERE reorder_level < 0;

ALTER TABLE bookings
    ADD INDEX idx_booking_vaccine_dose (vaccine_dose_id);

ALTER TABLE vaccination_schedules
    ADD INDEX idx_schedule_vaccine_dose (vaccine_dose_id);

ALTER TABLE vaccination_records
    ADD INDEX idx_record_vaccine_dose (vaccine_dose_id);

ALTER TABLE bookings
    ADD CONSTRAINT fk_booking_vaccine_dose
        FOREIGN KEY (vaccine_dose_id) REFERENCES vaccine_doses(id);

ALTER TABLE vaccination_schedules
    ADD CONSTRAINT fk_schedule_vaccine_dose
        FOREIGN KEY (vaccine_dose_id) REFERENCES vaccine_doses(id);

ALTER TABLE vaccination_records
    ADD CONSTRAINT fk_record_vaccine_dose
        FOREIGN KEY (vaccine_dose_id) REFERENCES vaccine_doses(id);

ALTER TABLE vaccine_doses
    ADD CONSTRAINT chk_vaccine_dose_number
        CHECK (dose_number > 0),
    ADD CONSTRAINT chk_vaccine_dose_age
        CHECK (recommended_age_days IS NULL OR recommended_age_days >= 0),
    ADD CONSTRAINT chk_vaccine_dose_interval
        CHECK (minimum_interval_days IS NULL OR minimum_interval_days >= 0);

ALTER TABLE hospital_inventory
    ADD CONSTRAINT chk_inventory_quantity
        CHECK (quantity >= 0),
    ADD CONSTRAINT chk_inventory_reorder_level
        CHECK (reorder_level >= 0);

ALTER TABLE hospital_slots
    ADD CONSTRAINT chk_slot_capacity
        CHECK (capacity > 0),
    ADD CONSTRAINT chk_slot_booked_count
        CHECK (booked_count >= 0 AND booked_count <= capacity);

CREATE TABLE IF NOT EXISTS inventory_transactions (
    id BIGINT NOT NULL AUTO_INCREMENT,
    hospital_id INT NOT NULL,
    vaccine_id INT NOT NULL,
    booking_id INT NULL,
    vaccination_record_id INT NULL,
    actor_user_id INT NULL,
    quantity_delta INT NOT NULL,
    quantity_after INT NOT NULL,
    reason VARCHAR(80) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_inventory_txn_item (hospital_id, vaccine_id, created_at),
    KEY idx_inventory_txn_record (vaccination_record_id),
    CONSTRAINT fk_inventory_txn_hospital
        FOREIGN KEY (hospital_id) REFERENCES hospitals(id),
    CONSTRAINT fk_inventory_txn_vaccine
        FOREIGN KEY (vaccine_id) REFERENCES vaccines(id),
    CONSTRAINT fk_inventory_txn_booking
        FOREIGN KEY (booking_id) REFERENCES bookings(id),
    CONSTRAINT fk_inventory_txn_record
        FOREIGN KEY (vaccination_record_id) REFERENCES vaccination_records(id),
    CONSTRAINT fk_inventory_txn_actor
        FOREIGN KEY (actor_user_id) REFERENCES users(id),
    CONSTRAINT chk_inventory_txn_after
        CHECK (quantity_after >= 0),
    CONSTRAINT chk_inventory_txn_delta
        CHECK (quantity_delta <> 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO inventory_transactions
    (hospital_id, vaccine_id, quantity_delta, quantity_after, reason)
SELECT i.hospital_id, i.vaccine_id, i.quantity, i.quantity, 'Opening balance'
FROM hospital_inventory i
WHERE i.quantity > 0
  AND NOT EXISTS (
      SELECT 1
      FROM inventory_transactions t
      WHERE t.hospital_id = i.hospital_id
        AND t.vaccine_id = i.vaccine_id
        AND t.reason = 'Opening balance'
  );

INSERT IGNORE INTO schema_migrations (version)
VALUES ('003_workflow_integrity');
