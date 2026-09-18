<?php

session_start();

include("../config/db.php");


// ===============================
// CHECK LOGIN
// ===============================

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit();
}

// ===============================
// CHECK HOSPITAL ROLE
// ===============================

if ($_SESSION["role"] !== "hospital") {
    header("Location: ../login.php");
    exit();
}

$name = $_SESSION["name"];
$user_id = $_SESSION["user_id"];

// ===============================
// GET HOSPITAL ID
// ===============================

$hospital_query = "SELECT id, hospital_name 
                   FROM hospitals 
                   WHERE user_id = '$user_id'
                   LIMIT 1";

$hospital_result = mysqli_query($conn, $hospital_query);

$hospital = mysqli_fetch_assoc($hospital_result);

if (!$hospital) {
    die("Hospital profile not found.");
}

$hospital_id = $hospital["id"];
$hospital_name = $hospital["hospital_name"];

// ===============================
// APPROVE / REJECT APPOINTMENT
// ===============================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (isset($_POST["booking_id"]) && isset($_POST["action"])) {

        $booking_id = intval($_POST["booking_id"]);
        $action = $_POST["action"];

 if ($action === "approve") {

    $update_query = "UPDATE bookings
                     SET status = 'Approved'
                     WHERE id = '$booking_id'
                     AND hospital_id = '$hospital_id'
                     AND status = 'Pending'";

    mysqli_query($conn, $update_query);

    // Check whether the appointment was actually approved
    if (mysqli_affected_rows($conn) > 0) {

        // Get parent and child information
        $booking_info_query = "SELECT
                                    b.parent_id,
                                    c.child_name,
                                    v.vaccine_name,
                                    v.dose_number
                                FROM bookings b
                                INNER JOIN children c
                                    ON b.child_id = c.id
                                INNER JOIN vaccines v
                                    ON b.vaccine_id = v.id
                                WHERE b.id = '$booking_id'
                                AND b.hospital_id = '$hospital_id'
                                LIMIT 1";

        $booking_info_result = mysqli_query($conn, $booking_info_query);

        if ($booking_info_result && mysqli_num_rows($booking_info_result) > 0) {

            $booking_info = mysqli_fetch_assoc($booking_info_result);

            $parent_id = $booking_info["parent_id"];
            $child_name = $booking_info["child_name"];
            $vaccine_name = $booking_info["vaccine_name"];
            $dose_number = $booking_info["dose_number"];

            // Create notification for parent
            $notification_query = "INSERT INTO notifications
                                   (user_id, title, message, type)
                                   VALUES (?, ?, ?, ?)";

            $notification_stmt = mysqli_prepare($conn, $notification_query);

            $title = "Appointment Approved";

            $notification_message = "Your vaccination appointment for "
                                  . $child_name
                                  . " (" . $vaccine_name
                                  . " - Dose " . $dose_number
                                  . ") has been approved.";

            $type = "appointment";

            mysqli_stmt_bind_param(
                $notification_stmt,
                "isss",
                $parent_id,
                $title,
                $notification_message,
                $type
            );

            mysqli_stmt_execute($notification_stmt);
            mysqli_stmt_close($notification_stmt);
        }
    }
}

        elseif ($action === "reject") {

            $update_query = "UPDATE bookings
                             SET status = 'Rejected'
                             WHERE id = '$booking_id'
                             AND hospital_id = '$hospital_id'
                             AND status = 'Pending'";

            mysqli_query($conn, $update_query);
        }

        header("Location: appointments.php");
        exit();
    }
}

// ===============================
// GET APPOINTMENTS
// ===============================

$appointments_query = "SELECT 
                        b.id,
                        b.booking_date,
                        b.booking_time,
                        b.status,

                        u.name AS parent_name,

                        c.child_name,

                        v.vaccine_name,
                        v.dose_number

                       FROM bookings b

                       INNER JOIN users u
                       ON b.parent_id = u.id

                       INNER JOIN children c
                       ON b.child_id = c.id

                       INNER JOIN vaccines v
                       ON b.vaccine_id = v.id

                       WHERE b.hospital_id = '$hospital_id'

                       ORDER BY b.booking_date ASC,
                                b.booking_time ASC";

$appointments_result = mysqli_query($conn, $appointments_query);

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