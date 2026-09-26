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
    $action = post_string("action", 20);
    $new_status = ["approve" => "Approved", "reject" => "Rejected"][$action] ?? "";

    if ($booking_id <= 0 || $new_status === "") {
        $message = "Invalid appointment action.";
        $message_type = "error";
    } else {
        mysqli_begin_transaction($conn);
        $stmt = $conn->prepare(
            "UPDATE bookings SET status = ?, updated_at = NOW()
             WHERE id = ? AND hospital_id = ? AND status = 'Pending'"
        );
        $stmt->bind_param("sii", $new_status, $booking_id, $hospital_id);
        $updated = $stmt->execute() && $stmt->affected_rows === 1;
        $stmt->close();

        if ($updated) {
            $info_stmt = $conn->prepare(
                "SELECT b.parent_id, c.child_name, v.vaccine_name, v.dose_number
                 FROM bookings b
                 JOIN children c ON c.id = b.child_id
                 JOIN vaccines v ON v.id = b.vaccine_id
                 WHERE b.id = ? AND b.hospital_id = ?"
            );
            $info_stmt->bind_param("ii", $booking_id, $hospital_id);
            $info_stmt->execute();
            $info = $info_stmt->get_result()->fetch_assoc();
            $info_stmt->close();

            $notice = $new_status === "Approved" ? "approved" : "rejected";
            $updated = $info && notify_user(
                $conn,
                (int)$info["parent_id"],
                "Appointment " . $notice,
                "Your appointment for " . $info["child_name"] . " (" . $info["vaccine_name"] . ") was " . $notice . ".",
                "appointment",
                "Parent/bookings.php"
            );

            if ($updated) {
                audit($conn, $user_id, "booking." . strtolower($new_status), "booking", $booking_id);
                mysqli_commit($conn);
                $message = "Appointment " . strtolower($new_status) . ".";
                $message_type = "success";
            } else {
                mysqli_rollback($conn);
                $message = "Unable to notify the parent.";
                $message_type = "error";
            }
        } else {
            mysqli_rollback($conn);
            $message = "This appointment is no longer pending.";
            $message_type = "error";
        }
    }
}

$appointments_stmt = $conn->prepare(
    "SELECT b.id, b.booking_date, b.booking_time, b.status,
            u.name AS parent_name, c.child_name,
            v.vaccine_name, v.dose_number
     FROM bookings b
     JOIN users u ON u.id = b.parent_id
     JOIN children c ON c.id = b.child_id
     JOIN vaccines v ON v.id = b.vaccine_id
     WHERE b.hospital_id = ?
     ORDER BY b.booking_date ASC, b.booking_time ASC"
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

    <title>Appointments | ImmuniCare</title>

    <link rel="stylesheet" href="../assets/css/style.css">

</head>


<body>

<div class="parent-dashboard">


    <!-- ================= SIDEBAR ================= -->

    <aside class="dashboard-sidebar">

        <div class="sidebar-brand">

            <img
                src="../assets/images/immunicare-logo-hospital-sidebar.svg"
                alt="ImmuniCare Hospital Portal"
                class="sidebar-brand-image"
            >

        </div>


        <nav class="sidebar-nav">

            <div class="nav-section-title">
                MAIN MENU
            </div>


            <a href="dashboard.php" class="sidebar-link">

                <span class="sidebar-icon">⌂</span>

                <span>Dashboard</span>

            </a>


            <a href="appointments.php" class="sidebar-link active">

                <span class="sidebar-icon">▣</span>

                <span>Appointments</span>

            </a>


            <a href="vaccinations.php" class="sidebar-link">

                <span class="sidebar-icon">✚</span>

                <span>Vaccinations</span>

            </a>


            <a href="schedule.php" class="sidebar-link">

                <span class="sidebar-icon">▤</span>

                <span>Vaccination Schedule</span>

            </a>


            <div class="nav-section-title dashboard-nav-spacing">
                ACCOUNT
            </div>


            <a href="profile.php" class="sidebar-link">

                <span class="sidebar-icon">♙</span>

                <span>My Profile</span>

            </a>

        </nav>


        <div class="sidebar-bottom">

            <a href="logout.php" class="sidebar-link logout-link">

                <span class="sidebar-icon">↪</span>

                <span>Logout</span>

            </a>

        </div>

    </aside>



    <!-- ================= MAIN CONTENT ================= -->

    <main class="dashboard-main">


        <!-- HEADER -->

        <header class="dashboard-header">

            <div class="header-page-title">

                <h1>Appointments</h1>

                <p>Manage vaccination appointments</p>

            </div>


            <div class="header-actions">

                <button class="notification-button">

                    🔔

                    <span class="notification-dot"></span>

                </button>


                <div class="header-divider"></div>


                <div class="profile-mini">

                    <div class="profile-avatar">
                        <?php echo strtoupper(substr($name, 0, 1)); ?>
                    </div>


                    <div class="profile-info">

                        <strong>
                            <?php echo htmlspecialchars($name); ?>
                        </strong>

                        <span>Hospital</span>

                    </div>

                </div>

            </div>

        </header>



        <!-- CONTENT -->

        <div class="dashboard-content">


            <!-- PAGE HEADING -->

            <div class="section-heading">

                <div>

                    <h2>Appointment Requests</h2>

                    <p>
                        Review and manage appointments booked at
                        <?php echo htmlspecialchars($hospital_name); ?>.
                    </p>

                </div>

            </div>



            <!-- APPOINTMENTS CARD -->

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
                                        Date
                                    </th>

                                    <th style="text-align:left; padding:15px;">
                                        Time
                                    </th>

                                    <th style="text-align:left; padding:15px;">
                                        Status
                                    </th>

                                    <th style="text-align:left; padding:15px;">
                                        Action
                                    </th>

                                </tr>

                            </thead>


                            <tbody>


                            <?php while ($appointment = mysqli_fetch_assoc($appointments_result)): ?>

                                <tr>


                                    <td style="padding:15px;">

                                        <?php echo $appointment["id"]; ?>

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

                                    </td>


                                    <td style="padding:15px;">

                                        <?php
                                        echo date(
                                            "h:i A",
                                            strtotime($appointment["booking_time"])
                                        );
                                        ?>

                                    </td>


                                    <td style="padding:15px;">

                                        <?php echo htmlspecialchars($appointment["status"]); ?>

                                    </td>


                                    <td style="padding:15px;">


                                        <?php if ($appointment["status"] === "Pending"): ?>


                                            <form method="POST" style="display:inline;">
                                                <?php echo csrf_field(); ?>

                                                <input
                                                    type="hidden"
                                                    name="booking_id"
                                                    value="<?php echo $appointment["id"]; ?>"
                                                >

                                                <button
                                                    type="submit"
                                                    name="action"
                                                    value="approve"
                                                >
                                                    Approve
                                                </button>

                                            </form>


                                            <form method="POST" style="display:inline;">
                                                <?php echo csrf_field(); ?>

                                                <input
                                                    type="hidden"
                                                    name="booking_id"
                                                    value="<?php echo $appointment["id"]; ?>"
                                                >

                                                <button
                                                    type="submit"
                                                    name="action"
                                                    value="reject"
                                                >
                                                    Reject
                                                </button>

                                            </form>


                                        <?php else: ?>

                                            <span>
                                                No action
                                            </span>

                                        <?php endif; ?>


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

                        <h3>No Appointments Yet</h3>

                        <p>
                            There are currently no appointments booked at your hospital.
                        </p>

                    </div>


                <?php endif; ?>


            </div>


        </div>


    </main>


</div>

</body>

</html>