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

// Get parent's vaccination history
$history_query = "SELECT
                    vr.id,
                    c.child_name,
                    v.vaccine_name,
                    v.dose_number,
                    h.hospital_name,
                    h.city,
                    vr.vaccination_date,
                    vr.status,
                    vr.remarks
                  FROM vaccination_records vr
                  INNER JOIN children c
                  ON vr.child_id = c.id
                  INNER JOIN vaccines v
                  ON vr.vaccine_id = v.id
                  INNER JOIN hospitals h
                  ON vr.hospital_id = h.id
                  WHERE c.parent_id = '$parent_id'
                  ORDER BY vr.vaccination_date DESC";

$history_result = mysqli_query($conn, $history_query);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Vaccination History | ImmuniCare</title>

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

            <a href="bookings.php" class="sidebar-link">
                <span class="sidebar-icon">▤</span>
                <span>My Bookings</span>
            </a>


            <div class="nav-section-title dashboard-nav-spacing">
                HEALTH RECORDS
            </div>

            <a href="vaccination_history.php" class="sidebar-link active">
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

                <h1>Vaccination History</h1>

                <p>
                    View your children's completed vaccination records
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


        <!-- ================= HISTORY CONTENT ================= -->

        <section class="dashboard-content history-page-content">


            <!-- PAGE INTRO -->

            <div class="section-heading history-heading">

                <div>

                    <h2>Vaccination History</h2>

                    <p>
                        A record of vaccinations received by your children
                    </p>

                </div>

            </div>


            <!-- ================= HISTORY LIST ================= -->

            <?php if (mysqli_num_rows($history_result) > 0): ?>

                <div class="history-list">

                    <?php while ($record = mysqli_fetch_assoc($history_result)): ?>

                        <?php
                        $status_class = strtolower($record["status"]);
                        ?>

                        <div class="history-card">


                            <!-- CARD HEADER -->

                            <div class="history-card-header">

                                <div class="history-child">

                                    <div class="history-child-icon">
                                        ♙
                                    </div>

                                    <div>

                                        <span class="history-label">
                                            CHILD
                                        </span>

                                        <h3>
                                            <?php echo htmlspecialchars($record["child_name"]); ?>
                                        </h3>

                                    </div>

                                </div>


                                <span class="history-status <?php echo $status_class; ?>">
                                    <?php echo htmlspecialchars($record["status"]); ?>
                                </span>

                            </div>


                            <!-- VACCINE -->

                            <div class="history-vaccine">

                                <span class="history-label">
                                    VACCINATION
                                </span>

                                <h3>
                                    <?php echo htmlspecialchars($record["vaccine_name"]); ?>
                                </h3>

                                <span class="history-dose">
                                    Dose <?php echo htmlspecialchars($record["dose_number"]); ?>
                                </span>

                            </div>


                            <!-- DETAILS -->

                            <div class="history-details">

                                <div class="history-detail">

                                    <div class="history-detail-icon">
                                        ♙
                                    </div>

                                    <div>

                                        <span>
                                            HOSPITAL
                                        </span>

                                        <strong>
                                            <?php echo htmlspecialchars($record["hospital_name"]); ?>
                                        </strong>

                                        <small>
                                            <?php echo htmlspecialchars($record["city"]); ?>
                                        </small>

                                    </div>

                                </div>


                                <div class="history-detail">

                                    <div class="history-detail-icon">
                                        ▣
                                    </div>

                                    <div>

                                        <span>
                                            VACCINATION DATE
                                        </span>

                                        <strong>
                                            <?php
                                            echo date(
                                                "d M Y",
                                                strtotime($record["vaccination_date"])
                                            );
                                            ?>
                                        </strong>

                                    </div>

                                </div>

                            </div>


                            <!-- REMARKS -->

                            <?php if (!empty($record["remarks"])): ?>

                                <div class="history-remarks">

                                    <span class="history-label">
                                        REMARKS
                                    </span>

                                    <p>
                                        <?php echo htmlspecialchars($record["remarks"]); ?>
                                    </p>

                                </div>

                            <?php endif; ?>


                        </div>

                    <?php endwhile; ?>

                </div>


            <?php else: ?>


                <!-- EMPTY STATE -->

                <div class="history-empty-card">

                    <div class="history-empty-icon">
                        ✓
                    </div>

                    <h3>
                        No vaccination history yet
                    </h3>

                    <p>
                        Your children's completed vaccinations will appear
                        here once they are recorded by the hospital.
                    </p>

                    <a href="vaccines.php" class="dashboard-primary-btn">
                        View Vaccines
                    </a>

                </div>


            <?php endif; ?>


        </section>

    </main>

</div>

</body>

</html>