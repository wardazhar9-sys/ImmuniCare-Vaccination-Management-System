<?php
require_once "../includes/app.php";
$user = require_role($conn, "parent");
$name = $user["name"];
$parent_id = (int)$user["id"];

if (isset($_POST["mark_notifications_read"])) {
    verify_csrf();
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $stmt->bind_param("i", $parent_id);
    $stmt->execute();
    $stmt->close();
    exit;
}

$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM notifications WHERE user_id = ? AND is_read = 0");
$stmt->bind_param("i", $parent_id);
$stmt->execute();
$unread_notifications = (int)$stmt->get_result()->fetch_assoc()["total"];
$stmt->close();

$stmt = $conn->prepare(
    "SELECT title, message, type, is_read, created_at, link_url
     FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 8"
);
$stmt->bind_param("i", $parent_id);
$stmt->execute();
$notification_result = $stmt->get_result();

$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM children WHERE parent_id = ? AND archived_at IS NULL");
$stmt->bind_param("i", $parent_id);
$stmt->execute();
$total_children = (int)$stmt->get_result()->fetch_assoc()["total"];
$stmt->close();

$stmt = $conn->prepare(
    "SELECT COUNT(*) AS total FROM bookings
     WHERE parent_id = ? AND booking_date >= CURDATE()
     AND status IN ('Pending', 'Approved')"
);
$stmt->bind_param("i", $parent_id);
$stmt->execute();
$upcoming_appointments = (int)$stmt->get_result()->fetch_assoc()["total"];
$stmt->close();

$stmt = $conn->prepare(
    "SELECT COUNT(*) AS total FROM vaccination_records vr
     JOIN children c ON c.id = vr.child_id
     WHERE c.parent_id = ? AND vr.status = 'Vaccinated'"
);
$stmt->bind_param("i", $parent_id);
$stmt->execute();
$completed_vaccinations = (int)$stmt->get_result()->fetch_assoc()["total"];
$stmt->close();

$stmt = $conn->prepare(
    "SELECT COUNT(*) AS total FROM vaccination_schedules vs
     JOIN children c ON c.id = vs.child_id
     WHERE c.parent_id = ? AND vs.status IN ('Scheduled', 'Completed', 'Missed')"
);
$stmt->bind_param("i", $parent_id);
$stmt->execute();
$total_scheduled = (int)$stmt->get_result()->fetch_assoc()["total"];
$stmt->close();
$vaccination_progress = $total_scheduled > 0
    ? min(100, (int)round(($completed_vaccinations / $total_scheduled) * 100))
    : 0;

$stmt = $conn->prepare(
    "SELECT c.child_name, v.vaccine_name, vs.scheduled_date, vs.scheduled_time
     FROM vaccination_schedules vs
     JOIN children c ON c.id = vs.child_id
     JOIN vaccines v ON v.id = vs.vaccine_id
     WHERE c.parent_id = ? AND vs.status = 'Scheduled'
       AND TIMESTAMP(vs.scheduled_date, vs.scheduled_time) >= NOW()
     ORDER BY vs.scheduled_date, vs.scheduled_time LIMIT 1"
);
$stmt->bind_param("i", $parent_id);
$stmt->execute();
$upcoming_vaccination = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($upcoming_vaccination) {
    $upcoming_child_name = $upcoming_vaccination["child_name"];
    $upcoming_vaccine_name = $upcoming_vaccination["vaccine_name"];
    $upcoming_date = $upcoming_vaccination["scheduled_date"];
    $upcoming_time = $upcoming_vaccination["scheduled_time"];
}

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Parent Dashboard | ImmuniCare</title>

    <link rel="stylesheet" href="../assets/css/style.css">

</head>

<body>

    <div class="parent-dashboard">

        <!-- ================= SIDEBAR ================= -->
<?php include "sidebar.php"; ?>


        <!-- ================= MAIN CONTENT ================= -->

        <main class="dashboard-main">


            <!-- TOP HEADER -->

          <!-- TOP HEADER -->

<?php include "../includes/portal_header.php"; ?>

            <!-- DASHBOARD CONTENT -->

            <section class="dashboard-content">


                <!-- WELCOME BANNER -->

                <div class="welcome-banner">

                    <div class="welcome-text">

                        <span class="welcome-label">
                            IMMUNICARE PARENT PORTAL
                        </span>

                        <h2>
                            Good afternoon, <?php echo htmlspecialchars($name); ?> 👋
                        </h2>

                        <p>
                            Stay on top of your children's vaccinations
                            and keep their health records organized.
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
                        <p>Your vaccination activity at a glance</p>
                    </div>

                </div>


                <!-- STATISTICS -->

                <div class="dashboard-stats">


                    <div class="dashboard-stat-card">

                        <div class="stat-icon stat-icon-blue">
                            ♙
                        </div>

                        <div class="stat-information">

                            <span class="stat-label">
                                Registered Children
                            </span>

                            <strong class="stat-number">
                                <?php echo $total_children; ?>
                            </strong>

                            <span class="stat-description">
                                Children in your account
                            </span>

                        </div>

                    </div>


                    <div class="dashboard-stat-card">

                        <div class="stat-icon stat-icon-green">
                            ✓
                        </div>

                        <div class="stat-information">

                            <span class="stat-label">
                                Vaccinations Completed
                            </span>

                            <strong class="stat-number">
                                <?php echo $completed_vaccinations; ?>
                            </strong>

                            <span class="stat-description">
                                Completed vaccinations
                            </span>

                        </div>

                    </div>


                    <div class="dashboard-stat-card">

                        <div class="stat-icon stat-icon-orange">
                            ▣
                        </div>

                        <div class="stat-information">

                            <span class="stat-label">
                                Upcoming Appointments
                            </span>

                            <strong class="stat-number">
                                <?php echo $upcoming_appointments; ?>
                            </strong>

                            <span class="stat-description">
                                Scheduled appointments
                            </span>

                        </div>

                    </div>


                    <div class="dashboard-stat-card">

                        <div class="stat-icon stat-icon-purple">
                            %
                        </div>

                        <div class="stat-information">

                            <span class="stat-label">
                                Vaccination Progress
                            </span>

                            <strong class="stat-number">
                              <?php echo $vaccination_progress; ?>%
                            </strong>

                            <span class="stat-description">
                                Overall progress
                            </span>

                        </div>

                    </div>

                </div>


                <!-- LOWER DASHBOARD AREA -->

                <div class="dashboard-grid">


                    <!-- UPCOMING VACCINATION -->

<?php if ($upcoming_vaccination): ?>

    <div class="upcoming-vaccination-card">

        <div class="upcoming-card-header">

            <div>
                <span class="upcoming-card-label">
                    UPCOMING VACCINATION
                </span>

                <h3>
                    Next vaccination
                </h3>
            </div>

            <a href="schedule.php" class="card-link">
                View Schedule →
            </a>

        </div>


        <div class="upcoming-vaccine-main">

            <div class="upcoming-vaccine-icon">
                ✓
            </div>


            <div class="upcoming-vaccine-info">

                <span class="upcoming-child-label">
                    CHILD
                </span>

                <h4>
                    <?php echo htmlspecialchars($upcoming_child_name); ?>
                </h4>

                <p>
                    <?php echo htmlspecialchars($upcoming_vaccine_name); ?>
                </p>

            </div>


            <span class="upcoming-status">
                Scheduled
            </span>

        </div>


        <div class="upcoming-vaccine-details">


            <div class="upcoming-detail">

                <div class="upcoming-detail-icon">
                    📅
                </div>

                <div>

                    <span>
                        DATE
                    </span>

                    <strong>
                        <?php
                        echo date(
                            "d M Y",
                            strtotime($upcoming_date)
                        );
                        ?>
                    </strong>

                </div>

            </div>


            <div class="upcoming-detail">

                <div class="upcoming-detail-icon">
                    🕐
                </div>

                <div>

                    <span>
                        TIME
                    </span>

                    <strong>
                        <?php
                        echo date(
                            "h:i A",
                            strtotime($upcoming_time)
                        );
                        ?>
                    </strong>

                </div>

            </div>


        </div>

    </div>

<?php else: ?>

    <div class="empty-dashboard-state">

        <div class="empty-state-icon">
            ▣
        </div>

        <h4>No upcoming vaccinations</h4>

        <p>
            Your upcoming vaccination appointments
            will appear here.
        </p>

        <a href="book_appointment.php" class="dashboard-primary-btn">
            Book an Appointment
        </a>

    </div>

<?php endif; ?>

                    <!-- <div class="dashboard-card upcoming-card">

                        <div class="card-header">

                            <div>
                                <h3>Upcoming Vaccinations</h3>
                                <p>Your next scheduled vaccination</p>
                            </div>

                            <a href="schedule.php" class="card-link">
                                View Schedule →
                            </a>

                        </div>


                        <div class="empty-dashboard-state">

                            <div class="empty-state-icon">
                                ▣
                            </div>

                            <h4>No upcoming vaccinations</h4>

                            <p>
                                Your upcoming vaccination appointments
                                will appear here.
                            </p>

                            <a href="book_appointment.php" class="dashboard-primary-btn">
                                Book an Appointment
                            </a>

                        </div>

                    </div> -->


                    <!-- QUICK ACTIONS -->

                    <div class="dashboard-card">

                        <div class="card-header">

                            <div>
                                <h3>Quick Actions</h3>
                                <p>Common parent activities</p>
                            </div>

                        </div>


                        <div class="quick-actions">


                            <a href="children.php" class="quick-action">

                                <div class="quick-action-icon">
                                    +
                                </div>

                                <div>
                                    <strong>Add a Child</strong>
                                    <span>Register a new child</span>
                                </div>

                                <span class="quick-arrow">→</span>

                            </a>


                            <a href="vaccines.php" class="quick-action">

                                <div class="quick-action-icon">
                                    ✚
                                </div>

                                <div>
                                    <strong>Explore Vaccines</strong>
                                    <span>View available vaccines</span>
                                </div>

                                <span class="quick-arrow">→</span>

                            </a>


                            <a href="book_appointment.php" class="quick-action">

                                <div class="quick-action-icon">
                                    ▣
                                </div>

                                <div>
                                    <strong>Book Appointment</strong>
                                    <span>Schedule a vaccination</span>
                                </div>

                                <span class="quick-arrow">→</span>

                            </a>


                            <a href="vaccination_history.php" class="quick-action">

                                <div class="quick-action-icon">
                                    ✓
                                </div>

                                <div>
                                    <strong>View Health Records</strong>
                                    <span>Check vaccination history</span>
                                </div>

                                <span class="quick-arrow">→</span>

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
                            Keep your children's vaccinations up to date
                        </strong>

                        <p>
                            ImmuniCare helps you keep track of vaccination
                            schedules, appointments and health records in one place.
                        </p>

                    </div>

                </div>


            </section>

        </main>

    </div>

  


<script>

    const notificationButton =
        document.querySelector(".notification-button");

    const notificationDropdown =
        document.querySelector(".notification-dropdown");


    notificationButton.addEventListener("click", function (event) {

        event.stopPropagation();

        notificationDropdown.classList.toggle("show");


        // Mark notifications as read
        <?php if ($unread_notifications > 0): ?>

        fetch("dashboard.php", {

            method: "POST",

            headers: {
                "Content-Type":
                    "application/x-www-form-urlencoded"
            },

            body: "mark_notifications_read=1&_csrf=<?php echo csrf_token(); ?>"

        })
        .then(() => {

            // Remove red dot
            const notificationDot =
                document.querySelector(".notification-dot");

            if (notificationDot) {
                notificationDot.remove();
            }


            // Remove "1 new"
            const newCount =
                document.querySelector(
                    ".notification-dropdown-header span"
                );

            if (newCount) {
                newCount.remove();
            }

        });

        <?php endif; ?>

    });


    // Close dropdown when clicking outside
    document.addEventListener("click", function () {

        notificationDropdown.classList.remove("show");

    });


    // Don't close dropdown when clicking inside it
    notificationDropdown.addEventListener(
        "click",
        function (event) {

            event.stopPropagation();

        }
    );

</script>



</body>

</html>
