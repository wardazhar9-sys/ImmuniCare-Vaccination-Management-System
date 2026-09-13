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
</head>
<body>
    
 <h1>Vaccination Schedule</h1>

  <?php if (mysqli_num_rows($schedule_result) > 0): ?>

        <?php while ($schedule = mysqli_fetch_assoc($schedule_result)): ?>

            <div>

                <h3>
                    <?php echo htmlspecialchars($schedule["child_name"]); ?>
                </h3>

                <p>
                    Vaccine:
                    <?php echo htmlspecialchars($schedule["vaccine_name"]); ?>
                </p>

                <p>
                    Dose:
                    <?php echo htmlspecialchars($schedule["dose_number"]); ?>
                </p>

                <p>
                    Date:
                    <?php echo htmlspecialchars($schedule["scheduled_date"]); ?>
                </p>

                <p>
                    Time:
                    <?php echo htmlspecialchars($schedule["scheduled_time"]); ?>
                </p>

                <p>
                    Status:
                    <?php echo htmlspecialchars($schedule["status"]); ?>
                </p>

            </div>

        <?php endwhile; ?>

    <?php else: ?>

        <p>No vaccination schedules found.</p>

    <?php endif; ?>

</body>
</html>