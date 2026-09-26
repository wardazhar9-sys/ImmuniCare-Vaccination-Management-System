<?php

require_once __DIR__ . '/../../config/db.php';

$conn->query(
    "CREATE TABLE IF NOT EXISTS schema_migrations (
        version VARCHAR(40) NOT NULL PRIMARY KEY,
        applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
     ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
);

$files = glob(__DIR__ . '/../database/migrations/*.sql');
sort($files);

foreach ($files as $file) {
    $version = pathinfo($file, PATHINFO_FILENAME);
    $stmt = $conn->prepare("SELECT 1 FROM schema_migrations WHERE version = ?");
    $stmt->bind_param("s", $version);
    $stmt->execute();
    $applied = (bool)$stmt->get_result()->fetch_row();
    $stmt->close();

    if ($applied) {
        continue;
    }

    $sql = file_get_contents($file);
    if ($sql === false || !$conn->multi_query($sql)) {
        throw new RuntimeException("Migration failed: " . $version . ": " . $conn->error);
    }

    do {
        if ($result = $conn->store_result()) {
            $result->free();
        }
    } while ($conn->more_results() && $conn->next_result());

    if ($conn->errno) {
        throw new RuntimeException("Migration failed: " . $version . ": " . $conn->error);
    }

    $stmt = $conn->prepare(
        "INSERT IGNORE INTO schema_migrations (version) VALUES (?)"
    );
    $stmt->bind_param("s", $version);
    $stmt->execute();
    $stmt->close();
    echo "Applied {$version}\n";
}
