<?php

function create_booking_workflow(
    mysqli $conn,
    int $parentId,
    int $childId,
    int $vaccineId,
    int $hospitalId,
    string $date,
    string $time,
    int $slotId = 0
): array {
    if (
        $childId <= 0 || $vaccineId <= 0 || $hospitalId <= 0 ||
        !valid_date($date) || !valid_time($time) ||
        strtotime("$date $time") <= time()
    ) {
        return ['ok' => false, 'status' => 422, 'error' => 'Invalid future booking data.'];
    }

    $stmt = $conn->prepare(
        "SELECT child_name FROM children
         WHERE id = ? AND parent_id = ? AND archived_at IS NULL"
    );
    $stmt->bind_param('ii', $childId, $parentId);
    $stmt->execute();
    $child = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $stmt = $conn->prepare(
        "SELECT vaccine_name, dose_number FROM vaccines
         WHERE id = ? AND availability = 'Available'"
    );
    $stmt->bind_param('i', $vaccineId);
    $stmt->execute();
    $vaccine = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $stmt = $conn->prepare(
        "SELECT user_id FROM hospitals WHERE id = ? AND status = 'Active'"
    );
    $stmt->bind_param('i', $hospitalId);
    $stmt->execute();
    $hospital = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$child || !$vaccine || !$hospital) {
        return ['ok' => false, 'status' => 422, 'error' => 'Invalid child, vaccine, or hospital.'];
    }

    $stmt = $conn->prepare(
        "SELECT id FROM bookings
         WHERE child_id = ? AND booking_date = ? AND booking_time = ?
           AND status IN ('Pending', 'Approved') LIMIT 1"
    );
    $stmt->bind_param('iss', $childId, $date, $time);
    $stmt->execute();
    $conflict = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($conflict) {
        return ['ok' => false, 'status' => 409, 'error' => 'Booking conflict.'];
    }

    mysqli_begin_transaction($conn);

    if ($slotId > 0) {
        $slot_stmt = $conn->prepare(
            "SELECT id, capacity, booked_count, status, slot_date, slot_time
             FROM hospital_slots
             WHERE id = ? AND hospital_id = ? FOR UPDATE"
        );
        $slot_stmt->bind_param('ii', $slotId, $hospitalId);
    } else {
        $slot_stmt = $conn->prepare(
            "SELECT id, capacity, booked_count, status, slot_date, slot_time
             FROM hospital_slots
             WHERE hospital_id = ? AND slot_date = ? AND slot_time = ?
             FOR UPDATE"
        );
        $slot_stmt->bind_param('iss', $hospitalId, $date, $time);
    }
    $slot_stmt->execute();
    $slot = $slot_stmt->get_result()->fetch_assoc();
    $slot_stmt->close();

    if ($slot && ($slot['status'] !== 'Open' || $slot['booked_count'] >= $slot['capacity'])) {
        mysqli_rollback($conn);
        return ['ok' => false, 'status' => 409, 'error' => 'Appointment slot is full or closed.'];
    }

    $status = 'Pending';
    $slotId = (int)$slot['id'];
    $date = $slot['slot_date'];
    $time = substr($slot['slot_time'], 0, 5);
    $stmt = $conn->prepare(
        "INSERT INTO bookings
         (parent_id, child_id, hospital_id, vaccine_id, slot_id, booking_date, booking_time, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param('iiiiisss', $parentId, $childId, $hospitalId, $vaccineId, $slotId, $date, $time, $status);
    $saved = $stmt->execute();
    $bookingId = $stmt->insert_id;
    $stmt->close();

    if ($saved && $slot) {
        $slot_update = $conn->prepare(
            "UPDATE hospital_slots SET booked_count = booked_count + 1 WHERE id = ?"
        );
        $slot_update->bind_param('i', $slot['id']);
        $saved = $slot_update->execute();
        $slot_update->close();
    }

    if ($saved) {
        $saved = notify_user(
            $conn,
            (int)$hospital['user_id'],
            'New appointment',
            'A new appointment was booked for ' . $child['child_name'] . '.',
            'appointment',
            'Hospital/appointments.php'
        );
    }

    if (!$saved) {
        mysqli_rollback($conn);
        return ['ok' => false, 'status' => 500, 'error' => 'Unable to create booking.'];
    }

    audit($conn, $parentId, 'booking.created', 'booking', $bookingId);
    mysqli_commit($conn);

    return [
        'ok' => true,
        'status' => 201,
        'data' => ['id' => $bookingId, 'status' => $status]
    ];
}
