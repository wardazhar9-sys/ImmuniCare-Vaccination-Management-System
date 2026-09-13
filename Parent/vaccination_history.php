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

/* GET PARENT'S VACCINATION HISTORY */

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
</head>

<body>

<h1>Vaccination History</h1>

<?php if (mysqli_num_rows($history_result) > 0): ?>

    <?php while ($record = mysqli_fetch_assoc($history_result)): ?>

        <div>

            <p>
                <strong>Child:</strong>
                <?php echo htmlspecialchars($record["child_name"]); ?>
            </p>

            <p>
                <strong>Vaccine:</strong>
                <?php echo htmlspecialchars($record["vaccine_name"]); ?>
                - Dose <?php echo htmlspecialchars($record["dose_number"]); ?>
            </p>

            <p>
                <strong>Hospital:</strong>
                <?php echo htmlspecialchars($record["hospital_name"]); ?>
                - <?php echo htmlspecialchars($record["city"]); ?>
            </p>

            <p>
                <strong>Vaccination Date:</strong>
                <?php echo htmlspecialchars($record["vaccination_date"]); ?>
            </p>

            <p>
                <strong>Status:</strong>
                <?php echo htmlspecialchars($record["status"]); ?>
            </p>

            <p>
                <strong>Remarks:</strong>
                <?php echo htmlspecialchars($record["remarks"]); ?>
            </p>

            <hr>

        </div>

    <?php endwhile; ?>

<?php else: ?>

    <p>No vaccination history available yet.</p>

<?php endif; ?>

</body>

</html>