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
$user_id = intval($_SESSION["user_id"]);

$success_message = "";
$error_message = "";


// ===============================
// GET HOSPITAL ID
// ===============================

$hospital_query = "SELECT id, hospital_name
                   FROM hospitals
                   WHERE user_id = ?
                   LIMIT 1";

$hospital_stmt = mysqli_prepare($conn, $hospital_query);
mysqli_stmt_bind_param($hospital_stmt, "i", $user_id);
mysqli_stmt_execute($hospital_stmt);
$hospital_result = mysqli_stmt_get_result($hospital_stmt);
$hospital = mysqli_fetch_assoc($hospital_result);
mysqli_stmt_close($hospital_stmt);

if (!$hospital) {
    die("Hospital profile not found.");
}

$hospital_id = intval($hospital["id"]);
$hospital_name = $hospital["hospital_name"];


// ===============================
// RECORD VACCINATION
// ===============================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $booking_id = isset($_POST["booking_id"]) ? intval($_POST["booking_id"]) : 0;
    $vaccination_date = isset($_POST["vaccination_date"]) ? trim($_POST["vaccination_date"]) : "";
    $status = isset($_POST["status"]) ? trim($_POST["status"]) : "";
    $remarks = isset($_POST["remarks"]) ? trim($_POST["remarks"]) : "";

    $allowed_statuses = ["Vaccinated", "Not Vaccinated"];

    if ($booking_id <= 0 || empty($vaccination_date) || !in_array($status, $allowed_statuses)) {
        $error_message = "Please enter a valid vaccination date and status.";
    } else {

        // Confirm this approved booking belongs to the logged-in hospital and has a schedule.
        $booking_query = "SELECT
                            b.child_id,
                            b.vaccine_id
                          FROM bookings b
                          INNER JOIN vaccination_schedules vs
                          ON vs.child_id = b.child_id
                          AND vs.vaccine_id = b.vaccine_id
                          WHERE b.id = ?
                          AND b.hospital_id = ?
                          AND b.status = 'Approved'
                          LIMIT 1";

        $booking_stmt = mysqli_prepare($conn, $booking_query);
        mysqli_stmt_bind_param($booking_stmt, "ii", $booking_id, $hospital_id);
        mysqli_stmt_execute($booking_stmt);
        $booking_result = mysqli_stmt_get_result($booking_stmt);
        $booking = mysqli_fetch_assoc($booking_result);
        mysqli_stmt_close($booking_stmt);

        if (!$booking) {
            $error_message = "Invalid vaccination selection.";
        } else {

            $child_id = intval($booking["child_id"]);
            $vaccine_id = intval($booking["vaccine_id"]);

            // Prevent duplicate records for the same child, vaccine dose, and hospital.
            $duplicate_query = "SELECT id
                                FROM vaccination_records
                                WHERE child_id = ?
                                AND vaccine_id = ?
                                AND hospital_id = ?
                                LIMIT 1";

            $duplicate_stmt = mysqli_prepare($conn, $duplicate_query);
            mysqli_stmt_bind_param($duplicate_stmt, "iii", $child_id, $vaccine_id, $hospital_id);
            mysqli_stmt_execute($duplicate_stmt);
            $duplicate_result = mysqli_stmt_get_result($duplicate_stmt);
            $already_recorded = mysqli_num_rows($duplicate_result) > 0;
            mysqli_stmt_close($duplicate_stmt);

            if ($already_recorded) {
                $error_message = "This vaccination has already been recorded.";
            } else {

                $insert_query = "INSERT INTO vaccination_records
                                 (child_id, vaccine_id, hospital_id, vaccination_date, status, remarks)
                                 VALUES
                                 (?, ?, ?, ?, ?, ?)";

                $insert_stmt = mysqli_prepare($conn, $insert_query);
                mysqli_stmt_bind_param(
                    $insert_stmt,
                    "iiisss",
                    $child_id,
                    $vaccine_id,
                    $hospital_id,
                    $vaccination_date,
                    $status,
                    $remarks
                );

                if (mysqli_stmt_execute($insert_stmt)) {

                    $schedule_update = "UPDATE vaccination_schedules
                                        SET status = 'Completed'
                                        WHERE child_id = ?
                                        AND vaccine_id = ?";

                    $schedule_stmt = mysqli_prepare($conn, $schedule_update);
                    mysqli_stmt_bind_param($schedule_stmt, "ii", $child_id, $vaccine_id);
                    mysqli_stmt_execute($schedule_stmt);
                    mysqli_stmt_close($schedule_stmt);

                    $booking_update = "UPDATE bookings
                                       SET status = 'Completed'
                                       WHERE id = ?
                                       AND hospital_id = ?";

                    $booking_stmt = mysqli_prepare($conn, $booking_update);
                    mysqli_stmt_bind_param($booking_stmt, "ii", $booking_id, $hospital_id);
                    mysqli_stmt_execute($booking_stmt);
                    mysqli_stmt_close($booking_stmt);

                    mysqli_stmt_close($insert_stmt);

                    header("Location: vaccinations.php?success=1");
                    exit();
                } else {
                    $error_message = "Unable to record vaccination. Please try again.";
                    mysqli_stmt_close($insert_stmt);
                }
            }
        }
    }
}

if (isset($_GET["success"])) {
    $success_message = "Vaccination recorded successfully.";
}


// ===============================
// GET SCHEDULED VACCINATIONS
// ===============================

$vaccinations_query = "SELECT
                        b.id AS booking_id,
                        b.status AS booking_status,

                        c.child_name,

                        v.vaccine_name,
                        v.dose_number,

                        vs.scheduled_date,
                        vs.scheduled_time,
                        vs.status AS schedule_status,

                        vr.id AS record_id,
                        vr.status AS record_status,
                        vr.vaccination_date

                       FROM bookings b

                       INNER JOIN children c
                       ON b.child_id = c.id

                       INNER JOIN vaccines v
                       ON b.vaccine_id = v.id

                       INNER JOIN vaccination_schedules vs
                       ON vs.child_id = b.child_id
                       AND vs.vaccine_id = b.vaccine_id

                       LEFT JOIN vaccination_records vr
                       ON vr.child_id = b.child_id
                       AND vr.vaccine_id = b.vaccine_id
                       AND vr.hospital_id = b.hospital_id

                       WHERE b.hospital_id = ?
                       AND b.status IN ('Approved', 'Completed')

                       ORDER BY vs.scheduled_date ASC,
                                vs.scheduled_time ASC";

$vaccinations_stmt = mysqli_prepare($conn, $vaccinations_query);
mysqli_stmt_bind_param($vaccinations_stmt, "i", $hospital_id);
mysqli_stmt_execute($vaccinations_stmt);
$vaccinations_data = mysqli_stmt_get_result($vaccinations_stmt);

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Vaccinations | ImmuniCare</title>

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


            <a href="vaccinations.php" class="sidebar-link active">

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

                <h1>Vaccinations</h1>

                <p>Record and manage children's vaccination records.</p>

            </div>


            <div class="header-actions">

                <button class="notification-button" type="button">

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

                    <h2>Scheduled Vaccinations</h2>

                    <p>
                        Record vaccinations for appointments scheduled at
                        <?php echo htmlspecialchars($hospital_name); ?>.
                    </p>

                </div>

            </div>


            <?php if (!empty($success_message)): ?>

                <div class="dashboard-card" style="margin-bottom:20px; color:#15803D;">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>

            <?php endif; ?>


            <?php if (!empty($error_message)): ?>

                <div class="dashboard-card" style="margin-bottom:20px; color:#DC2626;">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>

            <?php endif; ?>


            <!-- VACCINATION LIST -->

            <div class="dashboard-card">


                <?php if (mysqli_num_rows($vaccinations_data) > 0): ?>


                    <div style="overflow-x:auto;">

                        <table style="width:100%; border-collapse:collapse;">

                            <thead>

                                <tr>

                                    <th style="text-align:left; padding:15px;">
                                        Child
                                    </th>

                                    <th style="text-align:left; padding:15px;">
                                        Vaccine
                                    </th>

                                    <th style="text-align:left; padding:15px;">
                                        Scheduled Date
                                    </th>

                                    <th style="text-align:left; padding:15px;">
                                        Scheduled Time
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


                            <?php while ($vaccination = mysqli_fetch_assoc($vaccinations_data)): ?>

                                <?php
                                $has_record = !empty($vaccination["record_id"]);
                                $status_text = $has_record ? $vaccination["record_status"] : $vaccination["schedule_status"];
                                if ($status_text === "Vaccinated") {
                                    $status_class = "completed";
                                } elseif ($status_text === "Not Vaccinated") {
                                    $status_class = "cancelled";
                                } else {
                                    $status_class = strtolower(str_replace(" ", "-", $status_text));
                                }
                                ?>


                                <tr>


                                    <td style="padding:15px;">

                                        <?php echo htmlspecialchars($vaccination["child_name"]); ?>

                                    </td>


                                    <td style="padding:15px;">

                                        <?php echo htmlspecialchars($vaccination["vaccine_name"]); ?>

                                        <br>

                                        <small>
                                            Dose <?php echo htmlspecialchars($vaccination["dose_number"]); ?>
                                        </small>

                                    </td>


                                    <td style="padding:15px;">

                                        <?php
                                        echo date(
                                            "d M Y",
                                            strtotime($vaccination["scheduled_date"])
                                        );
                                        ?>

                                    </td>


                                    <td style="padding:15px;">

                                        <?php
                                        echo date(
                                            "h:i A",
                                            strtotime($vaccination["scheduled_time"])
                                        );
                                        ?>

                                    </td>


                                    <td style="padding:15px;">

                                        <span class="schedule-status <?php echo htmlspecialchars($status_class); ?>">
                                            <?php echo htmlspecialchars($status_text); ?>
                                        </span>

                                        <?php if ($has_record): ?>

                                            <br>

                                            <small>
                                                <?php
                                                echo date(
                                                    "d M Y",
                                                    strtotime($vaccination["vaccination_date"])
                                                );
                                                ?>
                                            </small>

                                        <?php endif; ?>

                                    </td>


                                    <td style="padding:15px;">

                                        <?php if ($has_record): ?>

                                            <span class="history-status vaccinated">
                                                Completed
                                            </span>

                                        <?php else: ?>

                                            <form method="POST">

                                                <input
                                                    type="hidden"
                                                    name="booking_id"
                                                    value="<?php echo htmlspecialchars($vaccination["booking_id"]); ?>"
                                                >

                                                <input
                                                    type="date"
                                                    name="vaccination_date"
                                                    value="<?php echo date("Y-m-d"); ?>"
                                                    required
                                                >

                                                <select name="status" required>
                                                    <option value="Vaccinated">Vaccinated</option>
                                                    <option value="Not Vaccinated">Not Vaccinated</option>
                                                </select>

                                                <textarea
                                                    name="remarks"
                                                    rows="2"
                                                    placeholder="Remarks"
                                                ></textarea>

                                                <button type="submit">
                                                    Record Vaccination
                                                </button>

                                            </form>

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
                            ✓
                        </div>


                        <h3>No Scheduled Vaccinations</h3>


                        <p>
                            There are currently no scheduled vaccinations
                            waiting to be recorded for this hospital.
                        </p>

                    </div>


                <?php endif; ?>


            </div>


        </div>


    </main>


</div>

<?php mysqli_stmt_close($vaccinations_stmt); ?>

</body>

</html>
