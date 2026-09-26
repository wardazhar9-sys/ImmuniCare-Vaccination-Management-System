<?php

require_once "../includes/app.php";

$user = require_role($conn, "hospital");
$user_id = (int)$user["id"];
$stmt = $conn->prepare(
    "SELECT id, hospital_name FROM hospitals WHERE user_id = ? AND status = 'Active'"
);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$hospital = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$hospital) {
    http_response_code(403);
    exit("Hospital approval is required.");
}

$hospital_id = (int)$hospital["id"];
$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verify_csrf();
    $date = post_string("slot_date", 10);
    $time = post_string("slot_time", 5);
    $capacity = max(1, post_int("capacity"));

    if (!valid_date($date) || !valid_time($time) || strtotime("$date $time") <= time()) {
        $message = "Choose a valid future slot.";
    } else {
        $stmt = $conn->prepare(
            "INSERT INTO hospital_slots (hospital_id, slot_date, slot_time, capacity)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE capacity = VALUES(capacity), status = 'Open'"
        );
        $stmt->bind_param("issi", $hospital_id, $date, $time, $capacity);
        $saved = $stmt->execute();
        $stmt->close();
        $message = $saved ? "Slot saved." : "Unable to save slot.";
    }
}

$stmt = $conn->prepare(
    "SELECT slot_date, slot_time, capacity, booked_count, status
     FROM hospital_slots WHERE hospital_id = ? AND slot_date >= CURDATE()
     ORDER BY slot_date, slot_time"
);
$stmt->bind_param("i", $hospital_id);
$stmt->execute();
$slots = $stmt->get_result();
?>
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Slots | ImmuniCare</title><link rel="stylesheet" href="../assets/css/style.css"></head>
<body class="dashboard-body">
<div class="parent-dashboard">
<?php include "sidebar.php"; ?>
<main class="dashboard-main">
<?php include "../includes/portal_header.php"; ?><section class="dashboard-content">
    <h1>Appointment slots</h1>
    <?php if ($message !== ""): ?><div class="appointment-message"><?php echo e($message); ?></div><?php endif; ?>
    <div class="dashboard-card"><form method="POST" class="admin-tool-form">
        <?php echo csrf_field(); ?>
        <div class="tool-field"><label for="slot_date">Date</label><input id="slot_date" type="date" name="slot_date" min="<?php echo date("Y-m-d"); ?>" required></div>
        <div class="tool-field"><label for="slot_time">Time</label><input id="slot_time" type="time" name="slot_time" required></div>
        <div class="tool-field"><label for="capacity">Capacity</label><input id="capacity" type="number" name="capacity" min="1" value="1" required></div>
        <button type="submit">Save slot</button>
    </form></div>
    <div class="users-card"><table class="users-table"><thead><tr><th>Date</th><th>Time</th><th>Capacity</th><th>Booked</th><th>Status</th></tr></thead><tbody>
        <?php while ($slot = $slots->fetch_assoc()): ?><tr><td><?php echo e($slot["slot_date"]); ?></td><td><?php echo e($slot["slot_time"]); ?></td><td><?php echo (int)$slot["capacity"]; ?></td><td><?php echo (int)$slot["booked_count"]; ?></td><td><?php echo e($slot["status"]); ?></td></tr><?php endwhile; ?>
    </tbody></table></div>
</section></main>
</div>
</body>
</html>
