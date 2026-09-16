<?php

session_start();

include("../config/db.php");

// Check whether user is logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit();
}

// Check whether the logged-in user is a parent
if ($_SESSION["role"] !== "parent") {
    header("Location: ../login.php");
    exit();
}

$name = $_SESSION["name"];
$parent_id = $_SESSION["user_id"];

// Get vaccination schedules belonging to this parent
$schedule_query = "SELECT
                    c.child_name,
                    v.vaccine_name,
                    v.dose_number,
                    vs.scheduled_date,
                    vs.scheduled_time,
                    vs.status
                   FROM vaccination_schedules vs
                   INNER JOIN children c ON vs.child_id = c.id
                   INNER JOIN vaccines v ON vs.vaccine_id = v.id
                   WHERE c.parent_id = '$parent_id'
                   ORDER BY vs.scheduled_date ASC, vs.scheduled_time ASC";

$schedule_result = mysqli_query($conn, $schedule_query);

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
                src="../assets/images/immunicare-logo-sidebar.svg"
                alt="ImmuniCare Parent Portal"
                class="sidebar-brand-image"
            >

        </div>


        <!-- Navigation -->

        <nav class="sidebar-nav">

            <div class="nav-section-title">
                MAIN MENU
            </div>

            <a href="dashboard.php" class="sidebar-link">
                <span class="sidebar-icon">⌂</span>
                <span>Dashboard</span>
            </a>

            <a href="my_children.php" class="sidebar-link">
                <span class="sidebar-icon">♙</span>
                <span>My Children</span>
            </a>

            <a href="vaccines.php" class="sidebar-link">
                <span class="sidebar-icon">✚</span>
                <span>Vaccines</span>
            </a>

            <a href="schedule.php" class="sidebar-link active">
                <span class="sidebar-icon">▣</span>
                <span>Vaccination Schedule</span>
            </a>


            <div class="nav-section-title dashboard-nav-spacing">
                APPOINTMENTS
            </div>

            <a href="book_appointment.php" class="sidebar-link">
                <span class="sidebar-icon">＋</span>
                <span>Book Appointment</span>
            </a>

            <a href="bookings.php" class="sidebar-link">
                <span class="sidebar-icon">▤</span>
                <span>My Bookings</span>
            </a>


            <div class="nav-section-title dashboard-nav-spacing">
                HEALTH RECORDS
            </div>

            <a href="vaccination_history.php" class="sidebar-link">
                <span class="sidebar-icon">✓</span>
                <span>Vaccination History</span>
            </a>

            <a href="profile.php" class="sidebar-link">
                <span class="sidebar-icon">◯</span>
                <span>My Profile</span>
            </a>

        </nav>


        <!-- Sidebar Bottom -->

        <div class="sidebar-bottom">

            <a href="logout.php" class="logout-link">
                <span class="sidebar-icon">↪</span>
                <span>Logout</span>
            </a>

        </div>

    </aside>


    <!-- ================= MAIN CONTENT ================= -->

    <main class="dashboard-main">


        <!-- TOP HEADER -->

        <header class="dashboard-header">

            <div class="header-page-title">

                <h1>Vaccination Schedule</h1>

                <p>
                    View your children's upcoming vaccination schedules
                </p>

            </div>


            <div class="header-actions">

                <button class="notification-button" type="button">
                    <span>♢</span>
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

                        <span>Parent Account</span>

                    </div>

                </div>

            </div>

        </header>


        <!-- ================= SCHEDULE CONTENT ================= -->

        <section class="dashboard-content schedule-page-content">


            <!-- PAGE INTRO -->

            <div class="section-heading schedule-heading">

                <div>

                    <h2>Vaccination Schedule</h2>

                    <p>
                        Keep track of your children's upcoming vaccinations
                    </p>

                </div>

            </div>


            <!-- ================= SCHEDULE LIST ================= -->

            <?php if (mysqli_num_rows($schedule_result) > 0): ?>

                <div class="schedule-list">

                    <?php while ($schedule = mysqli_fetch_assoc($schedule_result)): ?>

                        <?php
                        $status_class = strtolower($schedule["status"]);
                        ?>

                        <div class="schedule-card">


                            <!-- CARD HEADER -->

                            <div class="schedule-card-header">

                                <div class="schedule-child">

                                    <div class="schedule-child-icon">
                                        ♙
                                    </div>

                                    <div>

                                        <span class="schedule-label">
                                            CHILD
                                        </span>

                                        <h3>
                                            <?php echo htmlspecialchars($schedule["child_name"]); ?>
                                        </h3>

                                    </div>

                                </div>


                                <span class="schedule-status <?php echo $status_class; ?>">
                                    <?php echo htmlspecialchars($schedule["status"]); ?>
                                </span>

                            </div>


                            <!-- VACCINE -->

                            <div class="schedule-vaccine">

                                <span class="schedule-label">
                                    VACCINATION
                                </span>

                                <h3>
                                    <?php echo htmlspecialchars($schedule["vaccine_name"]); ?>
                                </h3>

                                <span class="schedule-dose">
                                    Dose <?php echo htmlspecialchars($schedule["dose_number"]); ?>
                                </span>

                            </div>


                            <!-- DETAILS -->

                            <div class="schedule-details">

                                <div class="schedule-detail">

                                    <div class="schedule-detail-icon">
                                        ▣
                                    </div>

                                    <div>

                                        <span>
                                            DATE
                                        </span>

                                        <strong>
                                            <?php
                                            echo date(
                                                "d M Y",
                                                strtotime($schedule["scheduled_date"])
                                            );
                                            ?>
                                        </strong>

                                    </div>

                                </div>


                                <div class="schedule-detail">

                                    <div class="schedule-detail-icon">
                                        ◷
                                    </div>

                                    <div>

                                        <span>
                                            TIME
                                        </span>

                                        <strong>
                                            <?php
                                            echo date(
                                                "h:i A",
                                                strtotime($schedule["scheduled_time"])
                                            );
                                            ?>
                                        </strong>

                                    </div>

                                </div>

                            </div>


                        </div>

                    <?php endwhile; ?>

                </div>


            <?php else: ?>


                <!-- EMPTY STATE -->

                <div class="schedule-empty-card">

                    <div class="schedule-empty-icon">
                        ▣
                    </div>

                    <h3>
                        No vaccination schedules yet
                    </h3>

                    <p>
                        Your children's upcoming vaccination schedules
                        will appear here once they are assigned.
                    </p>

                    <a href="book_appointment.php" class="dashboard-primary-btn">
                        Book an Appointment
                    </a>

                </div>


            <?php endif; ?>


        </section>

    </main>

</div>

</body>

</html>