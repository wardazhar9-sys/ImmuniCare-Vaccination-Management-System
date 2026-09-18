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

$parent_id = $_SESSION["user_id"];

// Get vaccine selected from Vaccines page
$selected_vaccine_id = isset($_GET["vaccine_id"]) ? (int)$_GET["vaccine_id"] : 0;


/* =========================
   GET PARENT'S CHILDREN
   ========================= */

$children_query = "SELECT id, child_name
                   FROM children
                   WHERE parent_id = '$parent_id'
                   ORDER BY child_name ASC";

$children_result = mysqli_query($conn, $children_query);


/* =========================
   GET AVAILABLE VACCINES
   ========================= */

$vaccines_query = "SELECT id, vaccine_name, dose_number
                   FROM vaccines
                   WHERE availability = 'Available'
                   ORDER BY vaccine_name ASC";

$vaccines_result = mysqli_query($conn, $vaccines_query);

/* =========================
   GET ACTIVE HOSPITALS
   ========================= */

$hospitals_query = "SELECT id, hospital_name, city
                    FROM hospitals
                    WHERE status = 'Active'
                    ORDER BY hospital_name ASC";

$hospitals_result = mysqli_query($conn, $hospitals_query);

/* BOOK APPOINTMENT */

$message = "";
$message_type = "";

if (isset($_POST["book_appointment"])) {

    $child_id = $_POST["child_id"];
    $vaccine_id = $_POST["vaccine_id"];
    $hospital_id = $_POST["hospital_id"];
    $booking_date = $_POST["booking_date"];
    $booking_time = $_POST["booking_time"];

      $status = "Pending";

      $query = "INSERT INTO bookings
              (parent_id, child_id, hospital_id, vaccine_id, booking_date, booking_time, status)
              VALUES (?, ?, ?, ?, ?, ?, ?)";

 $stmt = mysqli_prepare($conn, $query);

  mysqli_stmt_bind_param(
        $stmt,
        "iiiisss",
        $parent_id,
        $child_id,
        $hospital_id,
        $vaccine_id,
        $booking_date,
        $booking_time,
        $status
    );

if (mysqli_stmt_execute($stmt)) {

    // Get the hospital's user ID
    $hospital_query = "SELECT user_id
                       FROM hospitals
                       WHERE id = '$hospital_id'";

    $hospital_result = mysqli_query($conn, $hospital_query);

    if ($hospital_result && mysqli_num_rows($hospital_result) > 0) {

        $hospital = mysqli_fetch_assoc($hospital_result);
        $hospital_user_id = $hospital["user_id"];

        // Get the child's name
        $child_query = "SELECT child_name
                        FROM children
                        WHERE id = '$child_id'
                        AND parent_id = '$parent_id'";

        $child_result = mysqli_query($conn, $child_query);

        $child = mysqli_fetch_assoc($child_result);
        $child_name = $child["child_name"];

        // Create notification for hospital
        $notification_query = "INSERT INTO notifications
                               (user_id, title, message, type)
                               VALUES (?, ?, ?, ?)";

        $notification_stmt = mysqli_prepare($conn, $notification_query);

        $title = "New Appointment";
        $notification_message = "A new vaccination appointment has been booked for " . $child_name . ".";
        $type = "appointment";

        mysqli_stmt_bind_param(
            $notification_stmt,
            "isss",
            $hospital_user_id,
            $title,
            $notification_message,
            $type
        );

        mysqli_stmt_execute($notification_stmt);
        mysqli_stmt_close($notification_stmt);
    }

    $message = "Appointment booked successfully! Your booking is now pending hospital approval.";
    $message_type = "success";

} else {
    $message = "Something went wrong. Please try again.";
    $message_type = "error";
}

      mysqli_stmt_close($stmt);
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment | ImmuniCare</title>

    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>

<div class="parent-dashboard">

    <!-- ================= SIDEBAR ================= -->

    <aside class="dashboard-sidebar">

        <div class="sidebar-brand">
            <img src="../assets/images/immunicare-logo-sidebar.svg"
                 alt="ImmuniCare Parent Portal"
                 class="sidebar-brand-image">
        </div>

        <nav class="sidebar-nav">

            <div class="nav-section-title">
                MAIN MENU
            </div>

            <a href="dashboard.php" class="sidebar-link">
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

            <a href="book_appointment.php" class="sidebar-link active">
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


        <div class="sidebar-bottom">

            <a href="logout.php" class="logout-link">
                <span class="sidebar-icon">↪</span>
                <span>Logout</span>
            </a>

        </div>

    </aside>


    <!-- ================= MAIN CONTENT ================= -->

    <main class="dashboard-main">


        <!-- ================= HEADER ================= -->

        <header class="dashboard-header">

            <div class="header-page-title">

                <h1>Book Appointment</h1>

                <p>
                    Schedule a vaccination appointment for your child
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
                        <?php echo strtoupper(substr($_SESSION["name"], 0, 1)); ?>
                    </div>

                    <div class="profile-info">

                        <strong>
                            <?php echo htmlspecialchars($_SESSION["name"]); ?>
                        </strong>

                        <span>
                            Parent Account
                        </span>

                    </div>

                </div>

            </div>

        </header>


        <!-- ================= BOOKING CONTENT ================= -->

        <section class="dashboard-content">


            <div class="section-heading">

                <div>

                    <h2>
                        Schedule an Appointment
                    </h2>

                    <p>
                        Select your child, vaccine, hospital and preferred appointment time.
                    </p>

                </div>

            </div>


            <?php if ($message != ""): ?>

                <div class="appointment-message <?php echo $message_type; ?>">

                    <?php echo htmlspecialchars($message); ?>

                </div>

            <?php endif; ?>


            <div class="booking-card">

                <form method="POST" class="booking-form">


                    <!-- CHILD -->

                    <div class="form-group">

                        <label for="child_id">
                            Select Child
                        </label>

                        <select name="child_id" id="child_id" required>

                            <option value="">
                                Select Child
                            </option>

                            <?php while ($child = mysqli_fetch_assoc($children_result)): ?>

                                <option value="<?php echo $child["id"]; ?>">

                                    <?php echo htmlspecialchars($child["child_name"]); ?>

                                </option>

                            <?php endwhile; ?>

                        </select>

                    </div>


                    <!-- VACCINE -->

                    <div class="form-group">

                        <label for="vaccine_id">
                            Select Vaccine
                        </label>

                        <select name="vaccine_id" id="vaccine_id" required>

                            <option value="">
                                Select Vaccine
                            </option>

                            <?php while ($vaccine = mysqli_fetch_assoc($vaccines_result)): ?>

                                <option value="<?php echo $vaccine["id"]; ?>"
                                    <?php echo ($vaccine["id"] == $selected_vaccine_id) ? "selected" : ""; ?>>

                                    <?php echo htmlspecialchars($vaccine["vaccine_name"]); ?>
                                    - Dose <?php echo htmlspecialchars($vaccine["dose_number"]); ?>

                                </option>

                            <?php endwhile; ?>

                        </select>

                    </div>


                    <!-- HOSPITAL -->

                    <div class="form-group">

                        <label for="hospital_id">
                            Select Hospital
                        </label>

                        <select name="hospital_id" id="hospital_id" required>

                            <option value="">
                                Select Hospital
                            </option>

                            <?php while ($hospital = mysqli_fetch_assoc($hospitals_result)): ?>

                                <option value="<?php echo $hospital["id"]; ?>">

                                    <?php echo htmlspecialchars($hospital["hospital_name"]); ?>
                                    -
                                    <?php echo htmlspecialchars($hospital["city"]); ?>

                                </option>

                            <?php endwhile; ?>

                        </select>

                    </div>


                    <!-- DATE -->

                    <div class="form-group">

                        <label for="booking_date">
                            Appointment Date
                        </label>

                        <input
                            type="date"
                            name="booking_date"
                            id="booking_date"
                            required
                        >

                    </div>


                    <!-- TIME -->

                    <div class="form-group">

                        <label for="booking_time">
                            Appointment Time
                        </label>

                        <input
                            type="time"
                            name="booking_time"
                            id="booking_time"
                            required
                        >

                    </div>


                    <!-- SUBMIT -->

                    <div class="booking-form-actions">

                        <button
                            type="submit"
                            name="book_appointment"
                            class="dashboard-primary-btn"
                        >
                            Book Appointment
                        </button>

                    </div>


                </form>

            </div>


        </section>


    </main>

</div>


</body>
</html>