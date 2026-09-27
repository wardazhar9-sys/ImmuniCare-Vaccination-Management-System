-- Reports and migration bookkeeping.

CREATE TABLE IF NOT EXISTS report_exports (
    id BIGINT NOT NULL AUTO_INCREMENT,
    requested_by INT NOT NULL,
    report_type VARCHAR(80) NOT NULL,
    format ENUM('CSV', 'HTML') NOT NULL DEFAULT 'CSV',
    filters JSON NULL,
    status ENUM('Pending', 'Ready', 'Failed') NOT NULL DEFAULT 'Ready',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_report_user_created (requested_by, created_at),
    CONSTRAINT fk_report_user
        FOREIGN KEY (requested_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO schema_migrations (version)
VALUES ('003_reporting_preferences');
