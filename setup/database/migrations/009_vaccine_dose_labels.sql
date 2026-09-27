-- Add a display label for dose names such as Dose 1 or Booster.
-- dose_number remains the sequential ordering key used for eligibility.

ALTER TABLE vaccine_doses
    ADD COLUMN IF NOT EXISTS dose_label VARCHAR(100) NULL AFTER dose_number;

UPDATE vaccine_doses
SET dose_label = CONCAT('Dose ', dose_number)
WHERE dose_label IS NULL OR dose_label = '';

INSERT IGNORE INTO schema_migrations (version)
VALUES ('009_vaccine_dose_labels');
