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

$children_query = "SELECT COUNT(*) AS total_children 
                   FROM children 
                   WHERE parent_id = '$parent_id'";

$children_result = mysqli_query($conn, $children_query);

$children_data = mysqli_fetch_assoc($children_result);

$total_children = $children_data["total_children"];

$appointments_query = "SELECT COUNT(*) AS upcoming_appointments
                       FROM bookings
                       WHERE parent_id = '$parent_id'
                       AND booking_date >= CURDATE()
                       AND status IN ('Pending', 'Approved')";

$appointments_result = mysqli_query($conn, $appointments_query);
$appointments_data = mysqli_fetch_assoc($appointments_result);

$upcoming_appointments = $appointments_data["upcoming_appointments"];


$completed_query = "SELECT COUNT(*) AS completed_vaccinations
                    FROM vaccination_records vr
                    INNER JOIN children c ON vr.child_id = c.id
                    WHERE c.parent_id = '$parent_id'
                    AND vr.status = 'Vaccinated'";

$completed_result = mysqli_query($conn, $completed_query);
$completed_data = mysqli_fetch_assoc($completed_result);

$completed_vaccinations = $completed_data["completed_vaccinations"];
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

                <a href="dashboard.php" class="sidebar-link active">
                    <span class="sidebar-icon">⌂</span>
                    <span>Dashboard</span>
                </a>

                <a href="children.php" class="sidebar-link">
                    <span class="sidebar-icon">♙</span>
                    <span>My Children</span>
                </a>

                <a href="vaccines.php" class="sidebar-link">
                    <span class="sidebar-icon">✚</span>
                    <span>Vaccines</span>
                </a>

                <a href="schedule.php" class="sidebar-link">
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

                    <h1>Dashboard</h1>

                    <p>
                        Manage your children's vaccination journey
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
                                0
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
                                0%
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

                    <div class="dashboard-card upcoming-card">

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

                    </div>


                    <!-- QUICK ACTIONS -->

                    <div class="dashboard-card">

                        <div class="card-header">

                            <div>
                                <h3>Quick Actions</h3>
                                <p>Common parent activities</p>
                            </div>

                        </div>


                        <div class="quick-actions">


                            <a href="add_child.php" class="quick-action">

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

</body>

</html>
