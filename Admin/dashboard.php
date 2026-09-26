<?php

require_once "../includes/app.php";
$admin = require_role($conn, "admin");
$admin_name = $admin["name"];
$admin_id = (int)$admin["id"];

$notification_stmt = $conn->prepare(
    "SELECT COUNT(*) AS total FROM notifications WHERE user_id = ? AND is_read = 0"
);
$notification_stmt->bind_param("i", $admin_id);
$notification_stmt->execute();
$admin_unread = (int)$notification_stmt->get_result()->fetch_assoc()["total"];
$notification_stmt->close();


/* =========================================================
   DASHBOARD STATISTICS
========================================================= */

// Registered Parents
$sql = "SELECT COUNT(*) AS total FROM users WHERE role = 'parent'";
$result = mysqli_query($conn, $sql);
$parents = mysqli_fetch_assoc($result)["total"];


// Registered Hospitals
$sql = "SELECT COUNT(*) AS total FROM hospitals";
$result = mysqli_query($conn, $sql);
$hospitals = mysqli_fetch_assoc($result)["total"];


// Registered Children
$sql = "SELECT COUNT(*) AS total FROM children";
$result = mysqli_query($conn, $sql);
$children = mysqli_fetch_assoc($result)["total"];


// Total Vaccines
$sql = "SELECT COUNT(*) AS total FROM vaccines";
$result = mysqli_query($conn, $sql);
$vaccines = mysqli_fetch_assoc($result)["total"];


// Total Bookings
$sql = "SELECT COUNT(*) AS total FROM bookings";
$result = mysqli_query($conn, $sql);
$total_bookings = mysqli_fetch_assoc($result)["total"];


// Pending Bookings
$sql = "SELECT COUNT(*) AS total FROM bookings WHERE status = 'Pending'";
$result = mysqli_query($conn, $sql);
$pending_bookings = mysqli_fetch_assoc($result)["total"];


// Completed Bookings
$sql = "SELECT COUNT(*) AS total FROM bookings WHERE status = 'Completed'";
$result = mysqli_query($conn, $sql);
$completed_bookings = mysqli_fetch_assoc($result)["total"];


// Vaccination Records
$sql = "SELECT COUNT(*) AS total FROM vaccination_records";
$result = mysqli_query($conn, $sql);
$vaccination_records = mysqli_fetch_assoc($result)["total"];


/* =========================================================
   RECENT BOOKINGS
========================================================= */

$recent_bookings_sql = "
    SELECT
        bookings.id,
        children.child_name,
        vaccines.vaccine_name,
        hospitals.hospital_name,
        bookings.booking_date,
        bookings.booking_time,
        bookings.status
    FROM bookings
    INNER JOIN children
        ON bookings.child_id = children.id
    INNER JOIN vaccines
        ON bookings.vaccine_id = vaccines.id
    INNER JOIN hospitals
        ON bookings.hospital_id = hospitals.id
    ORDER BY bookings.created_at DESC
    LIMIT 5
";

$recent_bookings_result = mysqli_query($conn, $recent_bookings_sql);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Dashboard - ImmuniCare</title>

    <link rel="stylesheet" href="../assets/css/style.css">

</head>


<body class="dashboard-body">


<div class="dashboard-layout">


    <!-- ================= SIDEBAR ================= -->
<?php include "sidebar.php"; ?>



    <!-- =====================================================
         MAIN
    ====================================================== -->

    <main class="dashboard-main">


        <!-- HEADER -->

        <?php include "../includes/portal_header.php"; ?>



        <!-- CONTENT -->

        <section class="dashboard-content">


            <!-- =================================================
                 WELCOME BANNER
            ================================================== -->

            <div class="welcome-banner">

                <div class="welcome-text">

                    <div class="welcome-label">
                        IMMUNICARE ADMIN PORTAL
                    </div>


                    <h2>
                        Welcome back, <?php echo htmlspecialchars($admin_name); ?> 👋
                    </h2>


                    <p>
                        Monitor and manage the ImmuniCare vaccination
                        system, appointments, hospitals and vaccination
                        records.
                    </p>

                </div>


                <div class="welcome-decoration">

                    <div class="health-icon">

                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.5"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <path d="M20.8 8.7c0 5.5-8.8 10.3-8.8 10.3S3.2 14.2 3.2 8.7A4.7 4.7 0 0 1 12 6.3a4.7 4.7 0 0 1 8.8 2.4Z"></path>
                        </svg>

                    </div>

                </div>

            </div>



            <!-- =================================================
                 OVERVIEW
            ================================================== -->

            <div class="section-heading">

                <div>

                    <h3>Overview</h3>

                    <p>
                        Your vaccination system activity at a glance
                    </p>

                </div>

            </div>


            <!-- =================================================
                 FIRST ROW OF STATS
            ================================================== -->

           <div class="dashboard-stats">

    <!-- REGISTERED PARENTS -->
    <div class="dashboard-stat-card">

        <div class="stat-icon stat-icon-blue">
            ♙
        </div>

        <div class="stat-information">

            <span class="stat-label">
                Registered Parents
            </span>

            <span class="stat-number">
                <?php echo $parents; ?>
            </span>

            <span class="stat-description">
                Parent accounts
            </span>

        </div>

    </div>


    <!-- REGISTERED HOSPITALS -->
    <div class="dashboard-stat-card">

        <div class="stat-icon stat-icon-green">
            ♜
        </div>

        <div class="stat-information">

            <span class="stat-label">
                Registered Hospitals
            </span>

            <span class="stat-number">
                <?php echo $hospitals; ?>
            </span>

            <span class="stat-description">
                Participating hospitals
            </span>

        </div>

    </div>


    <!-- REGISTERED CHILDREN -->
    <div class="dashboard-stat-card">

        <div class="stat-icon stat-icon-orange">
            ♙
        </div>

        <div class="stat-information">

            <span class="stat-label">
                Registered Children
            </span>

            <span class="stat-number">
                <?php echo $children; ?>
            </span>

            <span class="stat-description">
                Children's profiles
            </span>

        </div>

    </div>


    <!-- AVAILABLE VACCINES -->
    <div class="dashboard-stat-card">

        <div class="stat-icon stat-icon-purple">
            ✚
        </div>

        <div class="stat-information">

            <span class="stat-label">
                Available Vaccines
            </span>

            <span class="stat-number">
                <?php echo $vaccines; ?>
            </span>

            <span class="stat-description">
                Vaccine records
            </span>

        </div>

    </div>

</div>



          <!-- SECOND ROW OF ADMIN STATISTICS -->

<div class="dashboard-stats">

    <!-- TOTAL BOOKINGS -->
    <div class="dashboard-stat-card">

        <div class="stat-icon stat-icon-blue">
            ▤
        </div>

        <div class="stat-information">

            <span class="stat-label">
                Total Bookings
            </span>

            <span class="stat-number">
                <?php echo $total_bookings; ?>
            </span>

            <span class="stat-description">
                All appointments
            </span>

        </div>

    </div>


    <!-- PENDING BOOKINGS -->
    <div class="dashboard-stat-card">

        <div class="stat-icon stat-icon-orange">
            ◷
        </div>

        <div class="stat-information">

            <span class="stat-label">
                Pending Bookings
            </span>

            <span class="stat-number">
                <?php echo $pending_bookings; ?>
            </span>

            <span class="stat-description">
                Awaiting action
            </span>

        </div>

    </div>


    <!-- COMPLETED BOOKINGS -->
    <div class="dashboard-stat-card">

        <div class="stat-icon stat-icon-green">
            ✓
        </div>

        <div class="stat-information">

            <span class="stat-label">
                Completed Bookings
            </span>

            <span class="stat-number">
                <?php echo $completed_bookings; ?>
            </span>

            <span class="stat-description">
                Completed appointments
            </span>

        </div>

    </div>


    <!-- VACCINATIONS RECORDED -->
    <div class="dashboard-stat-card">

        <div class="stat-icon stat-icon-purple">
            ✓
        </div>

        <div class="stat-information">

            <span class="stat-label">
                Vaccinations Recorded
            </span>

            <span class="stat-number">
                <?php echo $vaccination_records; ?>
            </span>

            <span class="stat-description">
                Vaccination records
            </span>

        </div>

    </div>

</div>


            <!-- =================================================
                 LOWER DASHBOARD
            ================================================== -->

            <div class="dashboard-grid">


<!-- =================================================
     RECENT BOOKINGS
================================================= -->

<div class="dashboard-card admin-recent-bookings">

    <div class="dashboard-card-header">

        <div>
            <h3>Recent Bookings</h3>
            <p>Latest appointment activity</p>
        </div>

        <a href="bookings.php" class="dashboard-card-link">
            View All →
        </a>

    </div>


    <div class="admin-bookings-list">

        <?php if ($recent_bookings_result && mysqli_num_rows($recent_bookings_result) > 0): ?>

            <?php while ($booking = mysqli_fetch_assoc($recent_bookings_result)): ?>

                <div class="admin-booking-row">

                    <!-- ICON -->
                    <div class="admin-booking-icon">
                        ▣
                    </div>


                    <!-- BOOKING DETAILS -->
                    <div class="admin-booking-details">

                        <strong>
                            <?php echo htmlspecialchars($booking["child_name"]); ?>
                        </strong>

                        <span>
                            <?php echo htmlspecialchars($booking["vaccine_name"]); ?>
                        </span>

                        <small>
                            <?php
                            echo htmlspecialchars(
                                $booking["booking_date"] . " at " . $booking["booking_time"]
                            );
                            ?>
                        </small>

                    </div>


                    <!-- STATUS -->
                    <div class="admin-booking-status">

                        <?php
                        $status = strtolower($booking["status"]);
                        ?>

                        <span class="admin-status <?php echo $status; ?>">
                            <?php echo htmlspecialchars($booking["status"]); ?>
                        </span>

                    </div>

                </div>

            <?php endwhile; ?>

        <?php else: ?>

            <div class="admin-bookings-empty">
                No recent bookings found.
            </div>

        <?php endif; ?>

    </div>

</div>


                <!-- QUICK ACTIONS -->

                <div class="dashboard-card">

                    <div class="dashboard-card-header">

                        <div>

                            <h3>
                                Quick Actions
                            </h3>

                            <p>
                                Common administrator activities
                            </p>

                        </div>

                    </div>


                    <div class="quick-actions">


                        <a href="users.php" class="quick-action">

                            <div class="quick-action-icon">

                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <circle cx="12" cy="7" r="4"></circle>
                                    <path d="M5 21a7 7 0 0 1 14 0"></path>
                                </svg>

                            </div>


                            <div class="quick-action-text">

                                <strong>
                                    Manage Users
                                </strong>

                                <span>
                                    View registered users
                                </span>

                            </div>


                            <span class="quick-action-arrow">
                                →
                            </span>

                        </a>


                        <a href="hospitals.php" class="quick-action">

                            <div class="quick-action-icon">

                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <path d="M3 21h18"></path>
                                    <path d="M5 21V6l7-3 7 3v15"></path>
                                    <path d="M9 21v-5h6v5"></path>
                                    <path d="M9 9h6"></path>
                                    <path d="M12 6v6"></path>
                                </svg>

                            </div>


                            <div class="quick-action-text">

                                <strong>
                                    Manage Hospitals
                                </strong>

                                <span>
                                    View hospital accounts
                                </span>

                            </div>


                            <span class="quick-action-arrow">
                                →
                            </span>

                        </a>


                        <a href="vaccines.php" class="quick-action">

                            <div class="quick-action-icon">

                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <path d="m18 2 4 4"></path>
                                    <path d="m17 7 3-3"></path>
                                    <path d="m3 21 9-9"></path>
                                    <path d="m14 4 6 6"></path>
                                </svg>

                            </div>


                            <div class="quick-action-text">

                                <strong>
                                    Manage Vaccines
                                </strong>

                                <span>
                                    Update vaccine information
                                </span>

                            </div>


                            <span class="quick-action-arrow">
                                →
                            </span>

                        </a>


                        <a href="bookings.php" class="quick-action">

                            <div class="quick-action-icon">

                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <rect x="4" y="4" width="16" height="16" rx="2"></rect>
                                    <path d="M8 9h8"></path>
                                    <path d="M8 13h8"></path>
                                    <path d="M8 17h5"></path>
                                </svg>

                            </div>


                            <div class="quick-action-text">

                                <strong>
                                    Review Bookings
                                </strong>

                                <span>
                                    View appointment requests
                                </span>

                            </div>


                            <span class="quick-action-arrow">
                                →
                            </span>

                        </a>


                    </div>

                </div>

            </div>


        </section>

    </main>

</div>


</body>

</html>