-- Bind every new booking to the hospital slot it reserves.

ALTER TABLE bookings
    ADD COLUMN IF NOT EXISTS slot_id INT NULL AFTER hospital_id;

UPDATE bookings b
JOIN hospital_slots s
    ON s.hospital_id = b.hospital_id
    AND s.slot_date = b.booking_date
    AND s.slot_time = b.booking_time
SET b.slot_id = s.id
WHERE b.slot_id IS NULL;

ALTER TABLE bookings
    ADD KEY idx_bookings_slot (slot_id),
    ADD CONSTRAINT fk_bookings_slot
        FOREIGN KEY (slot_id) REFERENCES hospital_slots(id);

INSERT IGNORE INTO schema_migrations (version)
VALUES ('004_booking_slots');
