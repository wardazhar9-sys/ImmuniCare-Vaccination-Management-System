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
</head>

<body>

<h1>Book Appointment</h1>

<?php if ($message != ""): ?>

    <p>
        <?php echo htmlspecialchars($message); ?>
    </p>

<?php endif; ?>


<form method="POST">

    <!-- CHILD -->
    <label>Select Child</label>
    <select name="child_id" required>
        <option value="">Select Child</option>

        <?php while ($child = mysqli_fetch_assoc($children_result)): ?>
            <option value="<?php echo $child["id"]; ?>">
                <?php echo htmlspecialchars($child["child_name"]); ?>
            </option>
        <?php endwhile; ?>

    </select>

    <br><br>

    <!-- VACCINE -->
    <label>Select Vaccine</label>
    <select name="vaccine_id" required>
        <option value="">Select Vaccine</option>

        <?php while ($vaccine = mysqli_fetch_assoc($vaccines_result)): ?>
            <option value="<?php echo $vaccine["id"]; ?>">
                <?php echo htmlspecialchars($vaccine["vaccine_name"]); ?>
                - Dose <?php echo htmlspecialchars($vaccine["dose_number"]); ?>
            </option>
        <?php endwhile; ?>

    </select>

    <br><br>

    <!-- HOSPITAL -->
    <label>Select Hospital</label>
    <select name="hospital_id" required>
        <option value="">Select Hospital</option>

        <?php while ($hospital = mysqli_fetch_assoc($hospitals_result)): ?>
            <option value="<?php echo $hospital["id"]; ?>">
                <?php echo htmlspecialchars($hospital["hospital_name"]); ?>
                - <?php echo htmlspecialchars($hospital["city"]); ?>
            </option>
        <?php endwhile; ?>

    </select>

    <br><br>

    <!-- DATE -->
    <label>Appointment Date</label>
    <input type="date" name="booking_date" required>

    <br><br>

    <!-- TIME -->
    <label>Appointment Time</label>
    <input type="time" name="booking_time" required>

    <br><br>

    <button type="submit" name="book_appointment">
        Book Appointment
    </button>

</form>

</body>
</html>