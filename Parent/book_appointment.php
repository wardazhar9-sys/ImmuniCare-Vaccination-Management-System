<?php
require_once "../includes/app.php";

$user = require_role($conn, "parent");
$parent_id = (int)$user["id"];
$selected_vaccine_id = (int)($_GET["vaccine_id"] ?? 0);
$message = "";
$message_type = "";

if (isset($_POST["book_appointment"])) {
    verify_csrf();
    $child_id = post_int("child_id");
    $vaccine_id = post_int("vaccine_id");
    $hospital_id = post_int("hospital_id");
    $booking_date = post_string("booking_date", 10);
    $booking_time = post_string("booking_time", 5);

    if (
        $child_id <= 0 || $vaccine_id <= 0 || $hospital_id <= 0 ||
        !valid_date($booking_date) || !valid_time($booking_time) ||
        strtotime("$booking_date $booking_time") <= time()
    ) {
        $message = "Choose a valid future appointment date and time.";
        $message_type = "error";
    } else {
        $child_stmt = $conn->prepare(
            "SELECT child_name FROM children
             WHERE id = ? AND parent_id = ? AND archived_at IS NULL"
        );
        $child_stmt->bind_param("ii", $child_id, $parent_id);
        $child_stmt->execute();
        $child = $child_stmt->get_result()->fetch_assoc();
        $child_stmt->close();

        $vaccine_stmt = $conn->prepare(
            "SELECT vaccine_name, dose_number FROM vaccines
             WHERE id = ? AND availability = 'Available'"
        );
        $vaccine_stmt->bind_param("i", $vaccine_id);
        $vaccine_stmt->execute();
        $vaccine = $vaccine_stmt->get_result()->fetch_assoc();
        $vaccine_stmt->close();

        $hospital_stmt = $conn->prepare(
            "SELECT id, user_id, hospital_name FROM hospitals
             WHERE id = ? AND status = 'Active'"
        );
        $hospital_stmt->bind_param("i", $hospital_id);
        $hospital_stmt->execute();
        $hospital = $hospital_stmt->get_result()->fetch_assoc();
        $hospital_stmt->close();

        if (!$child || !$vaccine || !$hospital) {
            $message = "The selected child, vaccine, or hospital is unavailable.";
            $message_type = "error";
        } else {
            $conflict_stmt = $conn->prepare(
                "SELECT id FROM bookings
                 WHERE child_id = ? AND booking_date = ? AND booking_time = ?
                 AND status IN ('Pending', 'Approved') LIMIT 1"
            );
            $conflict_stmt->bind_param("iss", $child_id, $booking_date, $booking_time);
            $conflict_stmt->execute();
            $conflict = $conflict_stmt->get_result()->fetch_assoc();
            $conflict_stmt->close();

            if ($conflict) {
                $message = "This child already has an appointment at that time.";
                $message_type = "error";
            } else {
                mysqli_begin_transaction($conn);
                $status = "Pending";
                $stmt = $conn->prepare(
                    "INSERT INTO bookings
                     (parent_id, child_id, hospital_id, vaccine_id,
                      booking_date, booking_time, status)
                     VALUES (?, ?, ?, ?, ?, ?, ?)"
                );
                $stmt->bind_param(
                    "iiiisss", $parent_id, $child_id, $hospital_id,
                    $vaccine_id, $booking_date, $booking_time, $status
                );
                $saved = $stmt->execute();
                $booking_id = $stmt->insert_id;
                $stmt->close();

                if ($saved) {
                    $saved = notify_user(
                        $conn,
                        (int)$hospital["user_id"],
                        "New appointment",
                        "A new vaccination appointment was booked for " . $child["child_name"] . ".",
                        "appointment",
                        "Hospital/appointments.php?booking_id=" . $booking_id
                    );
                }

                if ($saved) {
                    audit($conn, $parent_id, "booking.created", "booking", $booking_id);
                    mysqli_commit($conn);
                    $message = "Appointment booked and sent for hospital approval.";
                    $message_type = "success";
                } else {
                    mysqli_rollback($conn);
                    $message = "Unable to book the appointment.";
                    $message_type = "error";
                }
            }
        }
    }
}

$children_stmt = $conn->prepare(
    "SELECT id, child_name FROM children
     WHERE parent_id = ? AND archived_at IS NULL ORDER BY child_name"
);
$children_stmt->bind_param("i", $parent_id);
$children_stmt->execute();
$children_result = $children_stmt->get_result();

$vaccines_result = $conn->query(
    "SELECT id, vaccine_name, dose_number FROM vaccines
     WHERE availability = 'Available' ORDER BY vaccine_name"
);

$hospitals_result = $conn->query(
    "SELECT id, hospital_name, city FROM hospitals
     WHERE status = 'Active' ORDER BY hospital_name"
);

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
                    <?php echo csrf_field(); ?>


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