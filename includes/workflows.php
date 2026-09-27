<?php

function next_eligible_vaccine_dose(
    mysqli $conn,
    int $childId,
    int $vaccineId,
    bool $includeBooked = false
): ?array {
    $stmt = $conn->prepare(
        "SELECT c.date_of_birth,
                COALESCE(MAX(vr.dose_number), 0) AS completed_dose,
                MAX(vr.vaccination_date) AS last_vaccination_date
         FROM children c
         LEFT JOIN vaccination_records vr
           ON vr.child_id = c.id
          AND vr.vaccine_id = ?
          AND vr.status = 'Vaccinated'
         WHERE c.id = ?
         GROUP BY c.id, c.date_of_birth"
    );
    $stmt->bind_param("ii", $vaccineId, $childId);
    $stmt->execute();
    $progress = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$progress) {
        return null;
    }

    $nextDoseNumber = (int)$progress["completed_dose"] + 1;
    $stmt = $conn->prepare(
        "SELECT d.id, d.vaccine_id, d.dose_number,
                d.recommended_age_days, d.minimum_interval_days,
                d.clinical_source, v.vaccine_name
         FROM vaccine_doses d
         JOIN vaccines v ON v.id = d.vaccine_id
         WHERE d.vaccine_id = ? AND d.dose_number = ?
           AND d.status = 'Active' AND v.availability = 'Available'
         LIMIT 1"
    );
    $stmt->bind_param("ii", $vaccineId, $nextDoseNumber);
    $stmt->execute();
    $dose = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$dose) {
        return null;
    }

    $today = new DateTimeImmutable("today");
    $dateOfBirth = new DateTimeImmutable($progress["date_of_birth"]);
    if (
        $dose["recommended_age_days"] !== null &&
        $dateOfBirth->modify("+" . (int)$dose["recommended_age_days"] . " days") > $today
    ) {
        return null;
    }

    if (
        (int)$progress["completed_dose"] > 0 &&
        $progress["last_vaccination_date"] &&
        $dose["minimum_interval_days"] !== null
    ) {
        $lastVaccination = new DateTimeImmutable($progress["last_vaccination_date"]);
        if (
            $lastVaccination->modify(
                "+" . (int)$dose["minimum_interval_days"] . " days"
            ) > $today
        ) {
            return null;
        }
    }

    if (!$includeBooked) {
        $stmt = $conn->prepare(
            "SELECT id
             FROM bookings
             WHERE child_id = ? AND vaccine_dose_id = ?
               AND status IN ('Pending', 'Approved')
             LIMIT 1"
        );
        $stmt->bind_param("ii", $childId, $dose["id"]);
        $stmt->execute();
        $alreadyBooked = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($alreadyBooked) {
            return null;
        }
    }

    return $dose;
}

function vaccine_dose_for_booking(
    mysqli $conn,
    int $childId,
    int $vaccineId,
    int $vaccineDoseId = 0
): ?array {
    $nextDose = next_eligible_vaccine_dose($conn, $childId, $vaccineId, true);
    if (!$nextDose) {
        return null;
    }

    if ($vaccineDoseId > 0 && (int)$nextDose["id"] !== $vaccineDoseId) {
        return null;
    }

    $stmt = $conn->prepare(
        "SELECT id
         FROM bookings
         WHERE child_id = ? AND vaccine_dose_id = ?
           AND status IN ('Pending', 'Approved')
         LIMIT 1"
    );
    $stmt->bind_param("ii", $childId, $nextDose["id"]);
    $stmt->execute();
    $doseConflict = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($doseConflict) {
        return ["id" => 0, "reserved" => true];
    }

    return $nextDose;
}

function consume_inventory(
    mysqli $conn,
    int $hospitalId,
    int $vaccineId,
    int $bookingId,
    int $recordId,
    int $actorUserId
): array {
    if ($hospitalId <= 0 || $vaccineId <= 0 || $bookingId <= 0 || $recordId <= 0) {
        return ["ok" => false, "error" => "Invalid inventory reference."];
    }

    $stmt = $conn->prepare(
        "SELECT quantity
         FROM hospital_inventory
         WHERE hospital_id = ? AND vaccine_id = ?
         FOR UPDATE"
    );
    $stmt->bind_param("ii", $hospitalId, $vaccineId);
    $stmt->execute();
    $inventory = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$inventory || (int)$inventory["quantity"] < 1) {
        return ["ok" => false, "error" => "This vaccine is out of stock."];
    }

    $quantityAfter = (int)$inventory["quantity"] - 1;
    $stmt = $conn->prepare(
        "UPDATE hospital_inventory
         SET quantity = ?
         WHERE hospital_id = ? AND vaccine_id = ?"
    );
    $stmt->bind_param("iii", $quantityAfter, $hospitalId, $vaccineId);
    $updated = $stmt->execute();
    $stmt->close();

    if (!$updated) {
        return ["ok" => false, "error" => "Unable to update inventory."];
    }

    $reason = "Vaccination administered";
    $stmt = $conn->prepare(
        "INSERT INTO inventory_transactions
         (hospital_id, vaccine_id, booking_id, vaccination_record_id,
          actor_user_id, quantity_delta, quantity_after, reason)
         VALUES (?, ?, ?, ?, ?, -1, ?, ?)"
    );
    $stmt->bind_param(
        "iiiiiis",
        $hospitalId,
        $vaccineId,
        $bookingId,
        $recordId,
        $actorUserId,
        $quantityAfter,
        $reason
    );
    $saved = $stmt->execute();
    $stmt->close();

    return $saved
        ? ["ok" => true, "quantity_after" => $quantityAfter]
        : ["ok" => false, "error" => "Unable to record inventory transaction."];
}

function create_booking_workflow(
    mysqli $conn,
    int $parentId,
    int $childId,
    int $vaccineId,
    int $hospitalId,
    string $date,
    string $time,
    int $slotId = 0,
    int $vaccineDoseId = 0
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
        "SELECT id, vaccine_name, dose_number FROM vaccines
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

    $dose = vaccine_dose_for_booking(
        $conn,
        $childId,
        $vaccineId,
        $vaccineDoseId
    );
    if (!$dose) {
        return [
            'ok' => false,
            'status' => 422,
            'error' => 'This dose is not currently the next eligible dose for this child.'
        ];
    }
    if (!empty($dose["reserved"])) {
        return [
            'ok' => false,
            'status' => 409,
            'error' => 'This dose is already booked for this child.'
        ];
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

    if (!$slot) {
        mysqli_rollback($conn);
        return ['ok' => false, 'status' => 409, 'error' => 'Appointment slot is no longer available.'];
    }

    if ($slot['status'] !== 'Open' || $slot['booked_count'] >= $slot['capacity']) {
        mysqli_rollback($conn);
        return ['ok' => false, 'status' => 409, 'error' => 'Appointment slot is full or closed.'];
    }

    $status = 'Pending';
    $slotId = (int)$slot['id'];
    $date = $slot['slot_date'];
    $time = substr($slot['slot_time'], 0, 5);
    $stmt = $conn->prepare(
        "INSERT INTO bookings
         (parent_id, child_id, hospital_id, vaccine_id, vaccine_dose_id,
          slot_id, booking_date, booking_time, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param(
        'iiiiiisss',
        $parentId,
        $childId,
        $hospitalId,
        $vaccineId,
        $dose["id"],
        $slotId,
        $date,
        $time,
        $status
    );
    $saved = $stmt->execute();
    $bookingId = $stmt->insert_id;
    $stmt->close();

    if ($saved) {
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
        'data' => [
            'id' => $bookingId,
            'status' => $status,
            'vaccine_dose_id' => (int)$dose["id"],
            'dose_number' => (int)$dose["dose_number"]
        ]
    ];
}
