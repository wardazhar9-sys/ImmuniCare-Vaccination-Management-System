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
$hospital_data = $hospital_stmt->get_result()->fetch_assoc();
$hospital_stmt->close();

if (!$hospital_data) {
    http_response_code(403);
    exit("Hospital approval is required before using this portal.");
}

$hospital_id = (int)$hospital_data["id"];
$hospital_name = $hospital_data["hospital_name"];

if (isset($_POST["mark_notifications_read"])) {
    verify_csrf();
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
    exit;
}

function hospital_count(mysqli $conn, int $hospitalId, string $where): int
{
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM bookings WHERE hospital_id = ? AND $where");
    $stmt->bind_param("i", $hospitalId);
    $stmt->execute();
    $total = (int)$stmt->get_result()->fetch_assoc()["total"];
    $stmt->close();
    return $total;
}

$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM notifications WHERE user_id = ? AND is_read = 0");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$unread_notifications = (int)$stmt->get_result()->fetch_assoc()["total"];
$stmt->close();

$stmt = $conn->prepare(
    "SELECT title, message, type, is_read, created_at, link_url
     FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 8"
);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$notification_result = $stmt->get_result();

$total_pending = hospital_count($conn, $hospital_id, "status = 'Pending'");
$total_approved = hospital_count($conn, $hospital_id, "status = 'Approved'");
$total_today = hospital_count($conn, $hospital_id, "booking_date = CURDATE() AND status IN ('Pending', 'Approved')");

$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM vaccination_records WHERE hospital_id = ?");
$stmt->bind_param("i", $hospital_id);
$stmt->execute();
$total_vaccinations = (int)$stmt->get_result()->fetch_assoc()["total"];
$stmt->close();

$stmt = $conn->prepare(
    "SELECT COUNT(*) AS total
     FROM hospital_inventory
     WHERE hospital_id = ? AND quantity <= reorder_level"
);
$stmt->bind_param("i", $hospital_id);
$stmt->execute();
$low_stock_items = (int)$stmt->get_result()->fetch_assoc()["total"];
$stmt->close();

$stmt = $conn->prepare(
    "SELECT b.id, c.child_name, v.vaccine_name, v.dose_number,
            b.booking_date, b.booking_time, b.status
     FROM bookings b JOIN children c ON c.id = b.child_id
     JOIN vaccines v ON v.id = b.vaccine_id
     WHERE b.hospital_id = ? ORDER BY b.booking_date DESC, b.booking_time DESC LIMIT 5"
);
$stmt->bind_param("i", $hospital_id);
$stmt->execute();
$recent_result = $stmt->get_result();

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Hospital Dashboard | ImmuniCare</title>

    <link rel="stylesheet" href="../assets/css/style.css">

</head>


<body>

<div class="parent-dashboard">


    <!-- ================= SIDEBAR ================= -->
<?php include "sidebar.php"; ?>



    <!-- ================= MAIN CONTENT ================= -->

    <main class="dashboard-main">


        <!-- TOP HEADER -->

        <?php include "../includes/portal_header.php"; ?>



        <!-- DASHBOARD CONTENT -->

        <section class="dashboard-content">


            <!-- WELCOME BANNER -->

            <div class="welcome-banner">


                <div class="welcome-text">


                    <span class="welcome-label">
                        IMMUNICARE HOSPITAL PORTAL
                    </span>


                    <h2>

                        Welcome, <?php echo htmlspecialchars($hospital_name); ?> 👋

                    </h2>


                    <p>

                        Manage appointments, vaccination schedules
                        and vaccination records for your hospital.

                    </p>


                </div>


                <div class="welcome-decoration">


                    <div class="health-icon">
                        ♥
                    </div>


                </div>

            </div>



            <!-- OVERVIEW -->

            <div class="section-heading">


                <div>

                    <h2>Overview</h2>

                    <p>
                        Your hospital activity at a glance
                    </p>

                </div>


            </div>



            <!-- STATISTICS -->

            <div class="dashboard-stats">


                <!-- Pending -->

                <div class="dashboard-stat-card">


                    <div class="stat-icon stat-icon-blue">
                        ⏳
                    </div>


                    <div class="stat-information">


                        <span class="stat-label">
                            Pending Appointments
                        </span>


                        <strong class="stat-number">
                            <?php echo $total_pending; ?>
                        </strong>


                        <span class="stat-description">
                            Awaiting hospital action
                        </span>


                    </div>


                </div>



                <!-- Approved -->

                <div class="dashboard-stat-card">


                    <div class="stat-icon stat-icon-green">
                        ✓
                    </div>


                    <div class="stat-information">


                        <span class="stat-label">
                            Approved Appointments
                        </span>


                        <strong class="stat-number">
                            <?php echo $total_approved; ?>
                        </strong>


                        <span class="stat-description">
                            Confirmed appointments
                        </span>


                    </div>


                </div>



                <!-- Today's appointments -->

                <div class="dashboard-stat-card">


                    <div class="stat-icon stat-icon-orange">
                        ▣
                    </div>


                    <div class="stat-information">


                        <span class="stat-label">
                            Today's Appointments
                        </span>


                        <strong class="stat-number">
                            <?php echo $total_today; ?>
                        </strong>


                        <span class="stat-description">
                            Appointments for today
                        </span>


                    </div>


                </div>



                <!-- Vaccinations -->

                <div class="dashboard-stat-card">


                    <div class="stat-icon stat-icon-purple">
                        ✓
                    </div>


                    <div class="stat-information">


                        <span class="stat-label">
                            Vaccinations Recorded
                        </span>


                        <strong class="stat-number">
                            <?php echo $total_vaccinations; ?>
                        </strong>


                        <span class="stat-description">
                            Vaccinations completed
                        </span>


                    </div>


                </div>

                <!-- Inventory -->
                <div class="dashboard-stat-card">
                    <div class="stat-icon stat-icon-orange">▣</div>
                    <div class="stat-information">
                        <span class="stat-label">Low Stock Items</span>
                        <strong class="stat-number"><?php echo $low_stock_items; ?></strong>
                        <span class="stat-description">At or below reorder level</span>
                    </div>
                </div>


            </div>



            <!-- LOWER DASHBOARD AREA -->

            <div class="dashboard-grid">


                <!-- RECENT APPOINTMENTS -->

                <div class="dashboard-card">


                    <div class="card-header">


                        <div>

                            <h3>Recent Appointments</h3>

                            <p>
                                Latest appointment activity
                            </p>

                        </div>


                        <a href="appointments.php" class="card-link">
                            View All →
                        </a>


                    </div>



                    <?php if (mysqli_num_rows($recent_result) > 0): ?>


                        <div class="quick-actions">


                            <?php while ($appointment = mysqli_fetch_assoc($recent_result)): ?>


                                <div class="quick-action">


                                    <div class="quick-action-icon">
                                        ▣
                                    </div>


                                    <div>

                                        <strong>
                                            <?php echo htmlspecialchars($appointment["child_name"]); ?>
                                        </strong>


                                        <span>

                                            <?php echo htmlspecialchars($appointment["vaccine_name"]); ?>

                                            - Dose

                                            <?php echo htmlspecialchars($appointment["dose_number"]); ?>

                                            <br>

                                            <?php echo htmlspecialchars($appointment["booking_date"]); ?>

                                            at

                                            <?php echo htmlspecialchars($appointment["booking_time"]); ?>

                                        </span>

                                    </div>


                                    <span class="quick-arrow">

                                        <?php echo htmlspecialchars($appointment["status"]); ?>

                                    </span>


                                </div>


                            <?php endwhile; ?>


                        </div>


                    <?php else: ?>


                        <div class="empty-dashboard-state">


                            <div class="empty-state-icon">
                                ▣
                            </div>


                            <h4>
                                No appointments yet
                            </h4>


                            <p>

                                Parent appointment bookings
                                will appear here.

                            </p>


                            <a href="appointments.php" class="dashboard-primary-btn">

                                View Appointments

                            </a>


                        </div>


                    <?php endif; ?>


                </div>



                <!-- QUICK ACTIONS -->

                <div class="dashboard-card">


                    <div class="card-header">


                        <div>

                            <h3>Quick Actions</h3>

                            <p>
                                Common hospital activities
                            </p>

                        </div>


                    </div>



                    <div class="quick-actions">


                        <a href="appointments.php" class="quick-action">


                            <div class="quick-action-icon">
                                +
                            </div>


                            <div>

                                <strong>
                                    Manage Appointments
                                </strong>

                                <span>
                                    Review parent bookings
                                </span>

                            </div>


                            <span class="quick-arrow">
                                →
                            </span>


                        </a>



                        <a href="schedule.php" class="quick-action">


                            <div class="quick-action-icon">
                                ▣
                            </div>


                            <div>

                                <strong>
                                    Vaccination Schedule
                                </strong>

                                <span>
                                    Manage vaccination schedules
                                </span>

                            </div>


                            <span class="quick-arrow">
                                →
                            </span>


                        </a>



                        <a href="vaccinations.php" class="quick-action">


                            <div class="quick-action-icon">
                                ✓
                            </div>


                            <div>

                                <strong>
                                    Record Vaccination
                                </strong>

                                <span>
                                    Add a vaccination record
                                </span>

                            </div>


                            <span class="quick-arrow">
                                →
                            </span>


                        </a>



                        <a href="profile.php" class="quick-action">


                            <div class="quick-action-icon">
                                ◯
                            </div>


                            <div>

                                <strong>
                                    Hospital Profile
                                </strong>

                                <span>
                                    View hospital information
                                </span>

                            </div>


                            <span class="quick-arrow">
                                →
                            </span>


                        </a>


                    </div>


                </div>


            </div>



            <!-- INFORMATION SECTION -->

            <div class="dashboard-information">


                <div class="information-icon">
                    ✦
                </div>


                <div>


                    <strong>
                        Keep vaccination services organized
                    </strong>


                    <p>

                        ImmuniCare helps your hospital manage
                        appointments, vaccination schedules and
                        children's vaccination records in one place.

                    </p>


                </div>


            </div>


        </section>


    </main>


</div>



<script>



    const notificationButton = document.querySelector(".notification-button");
    const notificationDropdown = document.querySelector(".notification-dropdown");

    notificationButton.addEventListener("click", function (event) {

        event.stopPropagation();

        notificationDropdown.classList.toggle("show");

        fetch("dashboard.php", {
            method: "POST",
            headers: {"Content-Type": "application/x-www-form-urlencoded"},
            body: "mark_notifications_read=1&_csrf=<?php echo csrf_token(); ?>"
        });

    });

    document.addEventListener("click", function () {

        notificationDropdown.classList.remove("show");

    });

    notificationDropdown.addEventListener("click", function (event) {

        event.stopPropagation();

    });



</script>


</body>

</html>