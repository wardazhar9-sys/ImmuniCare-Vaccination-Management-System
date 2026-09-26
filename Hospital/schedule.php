<?php
require_once "../includes/app.php";

$user = require_role($conn, "hospital");
$user_id = (int)$user["id"];
$name = $user["name"];

$hospital_stmt = $conn->prepare(
    "SELECT id, hospital_name FROM hospitals
     WHERE user_id = ? AND status = 'Active' LIMIT 1"
);
$hospital_stmt->bind_param("i", $user_id);
$hospital_stmt->execute();
$hospital = $hospital_stmt->get_result()->fetch_assoc();
$hospital_stmt->close();

if (!$hospital) {
    http_response_code(403);
    exit("Hospital approval is required before using this portal.");
}

$hospital_id = (int)$hospital["id"];
$hospital_name = $hospital["hospital_name"];
$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verify_csrf();
    $booking_id = post_int("booking_id");
    $scheduled_date = post_string("scheduled_date", 10);
    $scheduled_time = post_string("scheduled_time", 5);

    if (
        $booking_id <= 0 || !valid_date($scheduled_date) ||
        !valid_time($scheduled_time) ||
        strtotime("$scheduled_date $scheduled_time") <= time()
    ) {
        $message = "Choose a valid future schedule.";
        $message_type = "error";
    } else {
        mysqli_begin_transaction($conn);
        $stmt = $conn->prepare(
            "SELECT b.parent_id, b.child_id, b.vaccine_id, v.dose_number, c.child_name,
                    v.vaccine_name
             FROM bookings b
             JOIN children c ON c.id = b.child_id
             JOIN vaccines v ON v.id = b.vaccine_id
             WHERE b.id = ? AND b.hospital_id = ? AND b.status = 'Approved'
             LIMIT 1"
        );
        $stmt->bind_param("ii", $booking_id, $hospital_id);
        $stmt->execute();
        $booking = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$booking) {
            mysqli_rollback($conn);
            $message = "Only approved appointments can be scheduled.";
            $message_type = "error";
        } else {
            $stmt = $conn->prepare(
                "SELECT id FROM vaccination_schedules WHERE booking_id = ? LIMIT 1"
            );
            $stmt->bind_param("i", $booking_id);
            $stmt->execute();
            $existing = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($existing) {
                mysqli_rollback($conn);
                $message = "This appointment already has a schedule.";
                $message_type = "error";
            } else {
                $status = "Scheduled";
                $stmt = $conn->prepare(
                    "INSERT INTO vaccination_schedules
                     (booking_id, child_id, vaccine_id, hospital_id, dose_number,
                      scheduled_date, scheduled_time, status)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
                );
                $stmt->bind_param(
                    "iiiiisss", $booking_id, $booking["child_id"],
                    $booking["vaccine_id"], $hospital_id, $booking["dose_number"],
                    $scheduled_date, $scheduled_time, $status
                );
                $saved = $stmt->execute();
                $schedule_id = $stmt->insert_id;
                $stmt->close();

                if ($saved) {
                    $saved = notify_user(
                        $conn,
                        (int)$booking["parent_id"],
                        "Vaccination scheduled",
                        "The appointment for " . $booking["child_name"] . " (" . $booking["vaccine_name"] . ") has been scheduled.",
                        "schedule",
                        "Parent/schedule.php"
                    );
                }

                if ($saved) {
                    audit($conn, $user_id, "schedule.created", "schedule", $schedule_id);
                    mysqli_commit($conn);
                    $message = "Vaccination schedule created.";
                    $message_type = "success";
                } else {
                    mysqli_rollback($conn);
                    $message = "Unable to create the schedule.";
                    $message_type = "error";
                }
            }
        }
    }
}

$appointments_stmt = $conn->prepare(
    "SELECT b.id AS booking_id, b.booking_date, b.booking_time,
            u.name AS parent_name, c.child_name,
            v.vaccine_name, v.dose_number
     FROM bookings b
     JOIN users u ON u.id = b.parent_id
     JOIN children c ON c.id = b.child_id
     JOIN vaccines v ON v.id = b.vaccine_id
     LEFT JOIN vaccination_schedules vs ON vs.booking_id = b.id
     WHERE b.hospital_id = ? AND b.status = 'Approved' AND vs.id IS NULL
     ORDER BY b.booking_date, b.booking_time"
);
$appointments_stmt->bind_param("i", $hospital_id);
$appointments_stmt->execute();
$appointments_result = $appointments_stmt->get_result();

?>
<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Vaccination Schedule | ImmuniCare</title>

    <link rel="stylesheet" href="../assets/css/style.css">

</head>


<body>

<div class="parent-dashboard">


    <!-- ================= SIDEBAR ================= -->
<?php include "sidebar.php"; ?>



    <!-- ================= MAIN CONTENT ================= -->

    <main class="dashboard-main">


        <!-- HEADER -->

        <?php include "../includes/portal_header.php"; ?>



        <!-- CONTENT -->

        <div class="dashboard-content">


            <div class="section-heading">

                <div>

                    <h2>Approved Appointments</h2>

                    <p>
                        Create vaccination schedules for approved appointments at
                        <?php echo htmlspecialchars($hospital_name); ?>.
                    </p>

                </div>

            </div>



            <!-- APPROVED APPOINTMENTS -->

            <div class="dashboard-card">


                <?php if (mysqli_num_rows($appointments_result) > 0): ?>


                    <div style="overflow-x:auto;">

                        <table style="width:100%; border-collapse:collapse;">

                            <thead>

                                <tr>

                                    <th style="text-align:left; padding:15px;">
                                        #
                                    </th>

                                    <th style="text-align:left; padding:15px;">
                                        Parent
                                    </th>

                                    <th style="text-align:left; padding:15px;">
                                        Child
                                    </th>

                                    <th style="text-align:left; padding:15px;">
                                        Vaccine
                                    </th>

                                    <th style="text-align:left; padding:15px;">
                                        Appointment
                                    </th>

                                    <th style="text-align:left; padding:15px;">
                                        Schedule
                                    </th>

                                </tr>

                            </thead>


                            <tbody>


                            <?php while ($appointment = mysqli_fetch_assoc($appointments_result)): ?>


                                <tr>


                                    <td style="padding:15px;">

                                        <?php echo $appointment["booking_id"]; ?>

                                    </td>


                                    <td style="padding:15px;">

                                        <?php echo htmlspecialchars($appointment["parent_name"]); ?>

                                    </td>


                                    <td style="padding:15px;">

                                        <?php echo htmlspecialchars($appointment["child_name"]); ?>

                                    </td>


                                    <td style="padding:15px;">

                                        <?php echo htmlspecialchars($appointment["vaccine_name"]); ?>

                                        <br>

                                        <small>
                                            Dose <?php echo $appointment["dose_number"]; ?>
                                        </small>

                                    </td>


                                    <td style="padding:15px;">

                                        <?php
                                        echo date(
                                            "d M Y",
                                            strtotime($appointment["booking_date"])
                                        );
                                        ?>

                                        <br>

                                        <?php
                                        echo date(
                                            "h:i A",
                                            strtotime($appointment["booking_time"])
                                        );
                                        ?>

                                    </td>


                                    <td style="padding:15px;">

                                        <form method="POST">
                                            <?php echo csrf_field(); ?>


                                            <input
                                                type="hidden"
                                                name="booking_id"
                                                value="<?php echo $appointment["booking_id"]; ?>"
                                            >


                                            <input
                                                type="date"
                                                name="scheduled_date"
                                                value="<?php echo $appointment["booking_date"]; ?>"
                                                required
                                            >


                                            <input
                                                type="time"
                                                name="scheduled_time"
                                                value="<?php echo $appointment["booking_time"]; ?>"
                                                required
                                            >


                                            <button
                                                type="submit"
                                            >
                                                Create Schedule
                                            </button>


                                        </form>

                                    </td>


                                </tr>


                            <?php endwhile; ?>


                            </tbody>

                        </table>

                    </div>


                <?php else: ?>


                    <div class="empty-dashboard-state">

                        <div class="empty-state-icon">
                            📅
                        </div>


                        <h3>No Approved Appointments</h3>


                        <p>
                            There are currently no approved appointments
                            waiting to be scheduled.
                        </p>

                    </div>


                <?php endif; ?>


            </div>


        </div>


    </main>


</div>

</body>

</html>