<?php

require_once "../../includes/api.php";

$user = api_user($conn);
$user_id = (int)$user['id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $conn->prepare(
        "SELECT id, title, message, type, is_read, created_at, link_url
         FROM notifications WHERE user_id = ?
         ORDER BY created_at DESC LIMIT 50"
    );
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $rows = [];
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $row['is_read'] = (bool)$row['is_read'];
        $rows[] = $row;
    }
    $stmt->close();
    api_response(['data' => $rows]);
}

api_method('POST');
$input = api_input();
$id = (int)($input['id'] ?? 0);

if ($id > 0) {
    $stmt = $conn->prepare(
        "UPDATE notifications SET is_read = 1, read_at = NOW()
         WHERE id = ? AND user_id = ?"
    );
    $stmt->bind_param('ii', $id, $user_id);
} else {
    $stmt = $conn->prepare(
        "UPDATE notifications SET is_read = 1, read_at = NOW()
         WHERE user_id = ?"
    );
    $stmt->bind_param('i', $user_id);
}

$stmt->execute();
$stmt->close();
api_response(['ok' => true]);
