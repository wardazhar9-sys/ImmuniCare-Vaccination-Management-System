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
// CREATE VACCINATION SCHEDULE
// ===============================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (
        isset($_POST["booking_id"]) &&
        isset($_POST["scheduled_date"]) &&
        isset($_POST["scheduled_time"])
    ) {

        $booking_id = intval($_POST["booking_id"]);
        $scheduled_date = $_POST["scheduled_date"];
        $scheduled_time = $_POST["scheduled_time"];


        // Get the approved booking
        $booking_query = "SELECT child_id, vaccine_id
                          FROM bookings
                          WHERE id = '$booking_id'
                          AND hospital_id = '$hospital_id'
                          AND status = 'Approved'
                          LIMIT 1";

        $booking_result = mysqli_query($conn, $booking_query);

        $booking = mysqli_fetch_assoc($booking_result);


        if ($booking) {

            $child_id = $booking["child_id"];
            $vaccine_id = $booking["vaccine_id"];


            // Insert vaccination schedule
            $schedule_query = "INSERT INTO vaccination_schedules
                               (child_id, vaccine_id, scheduled_date, scheduled_time, status)
                               VALUES
                               ('$child_id', '$vaccine_id', '$scheduled_date', '$scheduled_time', 'Scheduled')";

            mysqli_query($conn, $schedule_query);


            // Return to schedule page
            header("Location: schedule.php");
            exit();
        }
    }
}


// ===============================
// GET APPROVED APPOINTMENTS
// ===============================

$appointments_query = "SELECT
                        b.id AS booking_id,
                        b.booking_date,
                        b.booking_time,

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
                       AND b.status = 'Approved'

                       AND NOT EXISTS (
                           SELECT 1
                           FROM vaccination_schedules vs
                           WHERE vs.child_id = b.child_id
                           AND vs.vaccine_id = b.vaccine_id
                       )

                       ORDER BY b.booking_date ASC,
                                b.booking_time ASC";


$appointments_result = mysqli_query($conn, $appointments_query);

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


            <a href="appointments.php" class="sidebar-link">

                <span class="sidebar-icon">▣</span>

                <span>Appointments</span>

            </a>


            <a href="vaccinations.php" class="sidebar-link">

                <span class="sidebar-icon">✚</span>

                <span>Vaccinations</span>

            </a>


            <a href="schedule.php" class="sidebar-link active">

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

                <h1>Vaccination Schedule</h1>

                <p>Schedule approved vaccination appointments</p>

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