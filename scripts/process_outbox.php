<?php

require_once __DIR__ . '/../config/db.php';

$result = $conn->query(
    "SELECT id FROM notification_outbox
     WHERE status = 'Pending' AND available_at <= NOW()
     ORDER BY id LIMIT 100"
);

while ($row = $result->fetch_assoc()) {
    $stmt = $conn->prepare(
        "UPDATE notification_outbox
         SET status = 'Sent', sent_at = NOW(), attempts = attempts + 1
         WHERE id = ? AND status = 'Pending'"
    );
    $id = (int)$row['id'];
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
}

echo "Outbox processed\n";
