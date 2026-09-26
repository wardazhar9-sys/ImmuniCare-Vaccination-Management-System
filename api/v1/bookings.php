<?php

require_once "../../includes/api.php";
require_once "../../includes/workflows.php";

$user = api_user($conn);
$user_id = (int)$user['id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $sql = "SELECT b.id, c.child_name, v.vaccine_name, h.hospital_name,
                   b.booking_date, b.booking_time, b.status
            FROM bookings b
            JOIN children c ON c.id = b.child_id
            JOIN vaccines v ON v.id = b.vaccine_id
            JOIN hospitals h ON h.id = b.hospital_id";

    if ($user['role'] === 'parent') {
        $sql .= " WHERE b.parent_id = ?";
        $stmt = $conn->prepare($sql . " ORDER BY b.booking_date DESC, b.booking_time DESC");
        $stmt->bind_param('i', $user_id);
    } elseif ($user['role'] === 'hospital') {
        $stmt = $conn->prepare(
            $sql . " WHERE b.hospital_id IN
                    (SELECT id FROM hospitals WHERE user_id = ?)
                    ORDER BY b.booking_date DESC, b.booking_time DESC"
        );
        $stmt->bind_param('i', $user_id);
    } elseif ($user['role'] === 'admin') {
        $stmt = $conn->prepare($sql . " ORDER BY b.booking_date DESC, b.booking_time DESC");
    } else {
        api_response(['error' => 'Forbidden.'], 403);
    }

    $stmt->execute();
    $rows = [];
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    $stmt->close();
    api_response(['data' => $rows]);
}

api_method('POST');
if ($user['role'] !== 'parent') {
    api_response(['error' => 'Only parents can create bookings.'], 403);
}

$input = api_input();
$result = create_booking_workflow(
    $conn,
    (int)$user['id'],
    (int)($input['child_id'] ?? 0),
    (int)($input['vaccine_id'] ?? 0),
    (int)($input['hospital_id'] ?? 0),
    (string)($input['booking_date'] ?? ''),
    (string)($input['booking_time'] ?? ''),
    (int)($input['slot_id'] ?? 0)
);

if (!$result['ok']) {
    api_response(['error' => $result['error']], $result['status']);
}

api_response($result, 201);
