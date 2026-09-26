<?php

require_once "../includes/app.php";

$user = require_role($conn, "parent");
$parent_id = (int)$user["id"];

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    redirect_to("children.php");
}

verify_csrf();
$child_id = post_int("child_id");

$stmt = $conn->prepare(
    "SELECT child_name FROM children
     WHERE id = ? AND parent_id = ? AND archived_at IS NULL"
);
$stmt->bind_param("ii", $child_id, $parent_id);
$stmt->execute();
$child = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$child) {
    flash_set("Child not found.", "error");
    redirect_to("children.php");
}

$stmt = $conn->prepare(
    "SELECT COUNT(*) AS total FROM bookings WHERE child_id = ?"
);
$stmt->bind_param("i", $child_id);
$stmt->execute();
$has_bookings = (int)$stmt->get_result()->fetch_assoc()["total"] > 0;
$stmt->close();

$stmt = $conn->prepare(
    "SELECT COUNT(*) AS total FROM vaccination_records WHERE child_id = ?"
);
$stmt->bind_param("i", $child_id);
$stmt->execute();
$has_records = (int)$stmt->get_result()->fetch_assoc()["total"] > 0;
$stmt->close();

if ($has_bookings || $has_records) {
    flash_set("Children with medical history can only be archived.", "error");
    redirect_to("children.php");
}

$stmt = $conn->prepare(
    "UPDATE children SET archived_at = NOW()
     WHERE id = ? AND parent_id = ?"
);
$stmt->bind_param("ii", $child_id, $parent_id);
$stmt->execute();
$stmt->close();

audit($conn, $parent_id, "child.archived", "child", $child_id);
flash_set("Child archived successfully.");
redirect_to("children.php");
