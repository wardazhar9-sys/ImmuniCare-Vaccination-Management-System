<?php
session_start();
include("../config/db.php");

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit();
}

if ($_SESSION["role"] !== "parent") {
    header("Location: ../login.php");
    exit();
}

$parent_id = $_SESSION["user_id"];

$name = isset($_SESSION["name"]) ? $_SESSION["name"] : "";


/* GET PARENT'S BOOKINGS */

$bookings_query = "SELECT
                    b.id,
                    c.child_name,
                    v.vaccine_name,
                    v.dose_number,
                    h.hospital_name,
                    h.city,
                    b.booking_date,
                    b.booking_time,
                    b.status
                   FROM bookings b

                   INNER JOIN children c
                   ON b.child_id = c.id

                   INNER JOIN vaccines v
                   ON b.vaccine_id = v.id

                   INNER JOIN hospitals h
                   ON b.hospital_id = h.id

                   WHERE b.parent_id = '$parent_id'

                   ORDER BY b.booking_date DESC, b.booking_time DESC";

                $bookings_result = mysqli_query($conn, $bookings_query);

?>


<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Bookings | ImmuniCare</title>

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

        <a href="bookings.php" class="sidebar-link active">
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

        <a href="../logout.php" class="logout-link">
            <span class="sidebar-icon">↪</span>
            <span>Logout</span>
        </a>

    </div>

</aside>


    <!-- MAIN CONTENT -->
    <main class="dashboard-main">


    
       <!-- ================= TOP HEADER ================= -->

<header class="dashboard-header">

    <div class="header-page-title">

        <h1>My Bookings</h1>

        <p>
            View and manage your vaccination appointments
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


        <!-- PAGE CONTENT -->
        <section class="dashboard-content">


            <div class="section-heading">

                <div>

                    <h2>Appointment Bookings</h2>

                    <p>
                        Here you can view all vaccination appointments you have booked.
                    </p>

                </div>

                <a
                    href="book_appointment.php"
                    class="dashboard-primary-btn"
                >
                    + Book Appointment
                </a>

            </div>


            <?php if (mysqli_num_rows($bookings_result) > 0): ?>


                <div class="bookings-list">


                    <?php while ($booking = mysqli_fetch_assoc($bookings_result)): ?>


                        <?php

                        $status = strtolower($booking["status"]);

                        $status_class = "pending";

                        if ($status === "approved") {
                            $status_class = "approved";
                        } elseif ($status === "rejected") {
                            $status_class = "rejected";
                        } elseif ($status === "completed") {
                            $status_class = "completed";
                        }

                        ?>


                        <div class="booking-card">


                            <!-- BOOKING HEADER -->
                            <div class="booking-card-header">

                                <div>

                                    <span class="booking-label">
                                        Booking #<?php echo htmlspecialchars($booking["id"]); ?>
                                    </span>

                                    <h3 class="booking-vaccine-name">

                                        <?php echo htmlspecialchars($booking["vaccine_name"]); ?>

                                        <span>
                                            — Dose
                                            <?php echo htmlspecialchars($booking["dose_number"]); ?>
                                        </span>

                                    </h3>

                                </div>


                                <span class="booking-status <?php echo $status_class; ?>">

                                    <?php echo htmlspecialchars($booking["status"]); ?>

                                </span>

                            </div>


                            <!-- BOOKING DETAILS -->
                            <div class="booking-details">


                                <div class="booking-detail-item">

                                    <span class="booking-detail-icon">
                                        👶
                                    </span>

                                    <div>

                                        <span class="booking-detail-label">
                                            Child
                                        </span>

                                        <strong>
                                            <?php echo htmlspecialchars($booking["child_name"]); ?>
                                        </strong>

                                    </div>

                                </div>


                                <div class="booking-detail-item">

                                    <span class="booking-detail-icon">
                                        🏥
                                    </span>

                                    <div>

                                        <span class="booking-detail-label">
                                            Hospital
                                        </span>

                                        <strong>
                                            <?php echo htmlspecialchars($booking["hospital_name"]); ?>
                                        </strong>

                                        <span class="booking-detail-secondary">
                                            <?php echo htmlspecialchars($booking["city"]); ?>
                                        </span>

                                    </div>

                                </div>


                                <div class="booking-detail-item">

                                    <span class="booking-detail-icon">
                                        📅
                                    </span>

                                    <div>

                                        <span class="booking-detail-label">
                                            Appointment Date
                                        </span>

                                        <strong>
                                            <?php
                                            echo date(
                                                "d M Y",
                                                strtotime($booking["booking_date"])
                                            );
                                            ?>
                                        </strong>

                                    </div>

                                </div>


                                <div class="booking-detail-item">

                                    <span class="booking-detail-icon">
                                        🕐
                                    </span>

                                    <div>

                                        <span class="booking-detail-label">
                                            Appointment Time
                                        </span>

                                        <strong>
                                            <?php
                                            echo date(
                                                "h:i A",
                                                strtotime($booking["booking_time"])
                                            );
                                            ?>
                                        </strong>

                                    </div>

                                </div>


                            </div>


                            <!-- BOOKING FOOTER -->
                            <div class="booking-card-footer">

                                <?php if ($status === "pending"): ?>

                                    <span class="booking-footer-message">
                                        Your appointment is awaiting hospital approval.
                                    </span>

                                <?php elseif ($status === "approved"): ?>

                                    <span class="booking-footer-message">
                                        Your appointment has been approved.
                                    </span>

                                <?php elseif ($status === "rejected"): ?>

                                    <span class="booking-footer-message">
                                        This appointment was rejected by the hospital.
                                    </span>

                                <?php elseif ($status === "completed"): ?>

                                    <span class="booking-footer-message">
                                        This appointment has been completed.
                                    </span>

                                <?php endif; ?>


                            </div>


                        </div>


                    <?php endwhile; ?>


                </div>


            <?php else: ?>


                <!-- NO BOOKINGS -->
                <div class="no-bookings-card">

                    <div class="no-bookings-icon">
                        📋
                    </div>

                    <h3>
                        No bookings yet
                    </h3>

                    <p>
                        You haven't booked any vaccination appointments yet.
                    </p>

                    <a
                        href="book_appointment.php"
                        class="dashboard-primary-btn"
                    >
                        Book Your First Appointment
                    </a>

                </div>


            <?php endif; ?>


        </section>

    </main>

</div>

</body>

</html>