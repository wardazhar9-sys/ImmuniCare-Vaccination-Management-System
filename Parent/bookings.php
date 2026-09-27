<?php
require_once "../includes/app.php";
$user = require_role($conn, "parent");
$parent_id = (int)$user["id"];
$name = $user["name"];
$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verify_csrf();
    $booking_id = post_int("booking_id");
    $action = post_string("action", 20);
    $slot_id = post_int("slot_id");

    $stmt = $conn->prepare(
        "SELECT b.hospital_id, b.child_id, b.slot_id, b.status, h.user_id, c.child_name
         FROM bookings b JOIN hospitals h ON h.id = b.hospital_id
         JOIN children c ON c.id = b.child_id
         WHERE b.id = ? AND b.parent_id = ? LIMIT 1"
    );
    $stmt->bind_param("ii", $booking_id, $parent_id);
    $stmt->execute();
    $booking = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$booking || !in_array($booking["status"], ["Pending", "Approved"], true)) {
        $message = "This booking cannot be changed.";
        $message_type = "error";
    } elseif ($action === "cancel") {
        mysqli_begin_transaction($conn);
        $stmt = $conn->prepare(
            "UPDATE bookings SET status = 'Cancelled', cancelled_at = NOW(), updated_at = NOW()
             WHERE id = ? AND parent_id = ? AND status IN ('Pending', 'Approved')"
        );
        $stmt->bind_param("ii", $booking_id, $parent_id);
        $updated = $stmt->execute();
        $stmt->close();
        if ($updated && $booking["slot_id"]) {
            $slot_update = $conn->prepare(
                "UPDATE hospital_slots
                 SET booked_count = GREATEST(booked_count - 1, 0)
                 WHERE id = ?"
            );
            $slot_update->bind_param("i", $booking["slot_id"]);
            $updated = $slot_update->execute();
            $slot_update->close();
        }
        if ($updated) {
            mysqli_commit($conn);
            notify_user($conn, (int)$booking["user_id"], "Appointment cancelled", "An appointment for " . $booking["child_name"] . " was cancelled by the parent.", "appointment", "Hospital/appointments.php");
            audit($conn, $parent_id, "booking.cancelled", "booking", $booking_id);
            $message = "Appointment cancelled.";
            $message_type = "success";
        } else {
            mysqli_rollback($conn);
            $message = "Unable to cancel the appointment.";
            $message_type = "error";
        }
    } elseif ($action === "reschedule") {
        $slot_stmt = $conn->prepare(
            "SELECT slot_date, slot_time FROM hospital_slots
             WHERE id = ? AND hospital_id = ? AND status = 'Open'
               AND booked_count < capacity AND slot_date >= CURDATE()
             FOR UPDATE"
        );
        $slot_stmt->bind_param("ii", $slot_id, $booking["hospital_id"]);
        $slot_stmt->execute();
        $new_slot = $slot_stmt->get_result()->fetch_assoc();
        $slot_stmt->close();

        if (!$new_slot) {
            $message = "Choose an open slot at the same hospital.";
            $message_type = "error";
        } else {
            $date = $new_slot["slot_date"];
            $time = substr($new_slot["slot_time"], 0, 5);
            $conflict_stmt = $conn->prepare(
                "SELECT id FROM bookings
                 WHERE child_id = ? AND id <> ? AND booking_date = ? AND booking_time = ?
                   AND status IN ('Pending', 'Approved') LIMIT 1"
            );
            $conflict_stmt->bind_param(
                "iiss",
                $booking["child_id"],
                $booking_id,
                $date,
                $time
            );
            $conflict_stmt->execute();
            $conflict = $conflict_stmt->get_result()->fetch_assoc();
            $conflict_stmt->close();

            if ($conflict) {
                $message = "This child already has another appointment at that time.";
                $message_type = "error";
            } else {
                mysqli_begin_transaction($conn);
                if ($booking["slot_id"] && (int)$booking["slot_id"] !== $slot_id) {
                    $old_slot = $conn->prepare(
                        "UPDATE hospital_slots SET booked_count = GREATEST(booked_count - 1, 0)
                         WHERE id = ?"
                    );
                    $old_slot->bind_param("i", $booking["slot_id"]);
                    $updated = $old_slot->execute();
                    $old_slot->close();
                } else {
                    $updated = true;
                }

                $stmt = $conn->prepare(
                    "UPDATE bookings SET slot_id = ?, booking_date = ?, booking_time = ?, status = 'Pending', updated_at = NOW()
                     WHERE id = ? AND parent_id = ? AND status IN ('Pending', 'Approved')"
                );
                $stmt->bind_param("issii", $slot_id, $date, $time, $booking_id, $parent_id);
                $updated = $updated && $stmt->execute();
                $stmt->close();
                if ($updated && (int)$booking["slot_id"] !== $slot_id) {
                    $new_slot_update = $conn->prepare(
                        "UPDATE hospital_slots SET booked_count = booked_count + 1 WHERE id = ?"
                    );
                    $new_slot_update->bind_param("i", $slot_id);
                    $updated = $new_slot_update->execute();
                    $new_slot_update->close();
                }
                if ($updated) {
                    mysqli_commit($conn);
                    notify_user($conn, (int)$booking["user_id"], "Appointment rescheduled", "An appointment for " . $booking["child_name"] . " needs approval for its new time.", "appointment", "Hospital/appointments.php");
                    audit($conn, $parent_id, "booking.rescheduled", "booking", $booking_id);
                    $message = "Appointment rescheduled and sent for approval.";
                    $message_type = "success";
                } else {
                    mysqli_rollback($conn);
                    $message = "Unable to reschedule the appointment.";
                    $message_type = "error";
                }
            }
        }
    }
}

$stmt = $conn->prepare(
    "SELECT b.id, c.child_name, v.vaccine_name,
            COALESCE(d.dose_number, v.dose_number) AS dose_number,
            h.hospital_name, h.city, b.booking_date, b.booking_time,
            b.status
     FROM bookings b
     JOIN children c ON c.id = b.child_id
     JOIN vaccines v ON v.id = b.vaccine_id
     LEFT JOIN vaccine_doses d ON d.id = b.vaccine_dose_id
     JOIN hospitals h ON h.id = b.hospital_id
     WHERE b.parent_id = ?
     ORDER BY b.booking_date DESC, b.booking_time DESC"
);
$stmt->bind_param("i", $parent_id);
$stmt->execute();
$bookings_result = $stmt->get_result();

$slots_result = $conn->query(
    "SELECT s.id, s.slot_date, s.slot_time, s.capacity, s.booked_count,
            h.hospital_name, h.city
     FROM hospital_slots s JOIN hospitals h ON h.id = s.hospital_id
     WHERE s.status = 'Open' AND s.booked_count < s.capacity
       AND h.status = 'Active' AND s.slot_date >= CURDATE()
     ORDER BY s.slot_date, s.slot_time, h.hospital_name"
);

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Bookings | ImmuniCare</title>

    <link rel="stylesheet" href="../assets/css/style.css">

</head>

<body>

<div class="parent-dashboard">


   
<!-- ================= SIDEBAR ================= -->
<?php include "sidebar.php"; ?>


    <!-- MAIN CONTENT -->
    <main class="dashboard-main">


    
       <!-- ================= TOP HEADER ================= -->

<?php include "../includes/portal_header.php"; ?>


        <!-- PAGE CONTENT -->
        <section class="dashboard-content">


            <div class="section-heading">

                <div>

                    <h2>Appointment Bookings</h2>

                    <p>
                        Here you can view all vaccination appointments you have booked.
                    </p>

                </div>

                <a
                    href="book_appointment.php"
                    class="dashboard-primary-btn"
                >
                    + Book Appointment
                </a>

            </div>


            <?php if (mysqli_num_rows($bookings_result) > 0): ?>


                <div class="bookings-list">


                    <?php while ($booking = mysqli_fetch_assoc($bookings_result)): ?>


                        <?php

                        $status = strtolower($booking["status"]);

                        $status_class = "pending";

                        if ($status === "approved") {
                            $status_class = "approved";
                        } elseif ($status === "rejected") {
                            $status_class = "rejected";
                        } elseif ($status === "completed") {
                            $status_class = "completed";
                        }

                        ?>


                        <div class="booking-card">


                            <!-- BOOKING HEADER -->
                            <div class="booking-card-header">

                                <div>

                                    <span class="booking-label">
                                        Booking #<?php echo htmlspecialchars($booking["id"]); ?>
                                    </span>

                                    <h3 class="booking-vaccine-name">

                                        <?php echo htmlspecialchars($booking["vaccine_name"]); ?>

                                        <span>
                                            — Dose
                                            <?php echo htmlspecialchars($booking["dose_number"]); ?>
                                        </span>

                                    </h3>

                                </div>


                                <span class="booking-status <?php echo $status_class; ?>">

                                    <?php echo htmlspecialchars($booking["status"]); ?>

                                </span>

                            </div>


                            <!-- BOOKING DETAILS -->
                            <div class="booking-details">


                                <div class="booking-detail-item">

                                    <span class="booking-detail-icon">
                                        👶
                                    </span>

                                    <div>

                                        <span class="booking-detail-label">
                                            Child
                                        </span>

                                        <strong>
                                            <?php echo htmlspecialchars($booking["child_name"]); ?>
                                        </strong>

                                    </div>

                                </div>


                                <div class="booking-detail-item">

                                    <span class="booking-detail-icon">
                                        🏥
                                    </span>

                                    <div>

                                        <span class="booking-detail-label">
                                            Hospital
                                        </span>

                                        <strong>
                                            <?php echo htmlspecialchars($booking["hospital_name"]); ?>
                                        </strong>

                                        <span class="booking-detail-secondary">
                                            <?php echo htmlspecialchars($booking["city"]); ?>
                                        </span>

                                    </div>

                                </div>


                                <div class="booking-detail-item">

                                    <span class="booking-detail-icon">
                                        📅
                                    </span>

                                    <div>

                                        <span class="booking-detail-label">
                                            Appointment Date
                                        </span>

                                        <strong>
                                            <?php
                                            echo date(
                                                "d M Y",
                                                strtotime($booking["booking_date"])
                                            );
                                            ?>
                                        </strong>

                                    </div>

                                </div>


                                <div class="booking-detail-item">

                                    <span class="booking-detail-icon">
                                        🕐
                                    </span>

                                    <div>

                                        <span class="booking-detail-label">
                                            Appointment Time
                                        </span>

                                        <strong>
                                            <?php
                                            echo date(
                                                "h:i A",
                                                strtotime($booking["booking_time"])
                                            );
                                            ?>
                                        </strong>

                                    </div>

                                </div>


                            </div>


                            <!-- BOOKING FOOTER -->
                            <div class="booking-card-footer">

                                <?php if ($status === "pending"): ?>

                                    <span class="booking-footer-message">
                                        Your appointment is awaiting hospital approval.
                                    </span>

                                <?php elseif ($status === "approved"): ?>

                                    <span class="booking-footer-message">
                                        Your appointment has been approved.
                                    </span>

                                <?php elseif ($status === "rejected"): ?>

                                    <span class="booking-footer-message">
                                        This appointment was rejected by the hospital.
                                    </span>

                                <?php elseif ($status === "completed"): ?>

                                    <span class="booking-footer-message">
                                        This appointment has been completed.
                                    </span>

                                <?php endif; ?>

                                <?php if (in_array($status, ["pending", "approved"], true)): ?>
                                    <form method="POST" class="booking-inline-form">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="booking_id" value="<?php echo (int)$booking["id"]; ?>">
                                        <input type="hidden" name="action" value="reschedule">
                                        <select name="slot_id" required>
                                            <option value="">Choose an open slot</option>
                                            <?php
                                            mysqli_data_seek($slots_result, 0);
                                            while ($slot = mysqli_fetch_assoc($slots_result)):
                                            ?>
                                                <option value="<?php echo (int)$slot["id"]; ?>">
                                                    <?php echo e($slot["hospital_name"]); ?> -
                                                    <?php echo e($slot["slot_date"]); ?>
                                                    <?php echo e(date("h:i A", strtotime($slot["slot_time"]))); ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                        <button type="submit">Reschedule</button>
                                    </form>
                                    <form method="POST" class="booking-inline-form">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="booking_id" value="<?php echo (int)$booking["id"]; ?>">
                                        <input type="hidden" name="action" value="cancel">
                                        <button type="submit" class="user-action-deactivate">Cancel</button>
                                    </form>
                                <?php endif; ?>


                            </div>


                        </div>


                    <?php endwhile; ?>


                </div>


            <?php else: ?>


                <!-- NO BOOKINGS -->
                <div class="no-bookings-card">

                    <div class="no-bookings-icon">
                        📋
                    </div>

                    <h3>
                        No bookings yet
                    </h3>

                    <p>
                        You haven't booked any vaccination appointments yet.
                    </p>

                    <a
                        href="book_appointment.php"
                        class="dashboard-primary-btn"
                    >
                        Book Your First Appointment
                    </a>

                </div>


            <?php endif; ?>


        </section>

    </main>

</div>

</body>

</html>