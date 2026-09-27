-- Remove support tables that are not used by the current application.
-- This migration is safe for existing databases after a backup.

DROP TABLE IF EXISTS hospital_holidays;
DROP TABLE IF EXISTS hospital_hours;
DROP TABLE IF EXISTS user_preferences;
DROP TABLE IF EXISTS auth_events;

INSERT IGNORE INTO schema_migrations (version)
VALUES ('008_remove_unused_support_tables');
