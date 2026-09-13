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
</head>

<body>

<h1>My Bookings</h1>

<?php if (mysqli_num_rows($bookings_result) > 0): ?>

    <?php while ($booking = mysqli_fetch_assoc($bookings_result)): ?>

        <div>
            <p>
                <strong>Child:</strong>
                <?php echo htmlspecialchars($booking["child_name"]); ?>
            </p>

            <p>
                <strong>Vaccine:</strong>
                <?php echo htmlspecialchars($booking["vaccine_name"]); ?>
                - Dose <?php echo htmlspecialchars($booking["dose_number"]); ?>
            </p>

            <p>
                <strong>Hospital:</strong>
                <?php echo htmlspecialchars($booking["hospital_name"]); ?>
                - <?php echo htmlspecialchars($booking["city"]); ?>
            </p>

            <p>
                <strong>Date:</strong>
                <?php echo htmlspecialchars($booking["booking_date"]); ?>
            </p>

            <p>
                <strong>Time:</strong>
                <?php echo htmlspecialchars($booking["booking_time"]); ?>
            </p>

            <p>
                <strong>Status:</strong>
                <?php echo htmlspecialchars($booking["status"]); ?>
            </p>

            <hr>

        </div>

    <?php endwhile; ?>

<?php else: ?>

    <p>You have no bookings yet.</p>

<?php endif; ?>

</body>

</html>