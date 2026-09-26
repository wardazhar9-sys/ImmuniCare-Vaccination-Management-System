<?php

require_once "../includes/app.php";

$admin = require_role($conn, "admin");
$admin_id = (int)$admin["id"];
$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verify_csrf();
    $vaccine_id = post_int("vaccine_id");
    $dose_number = post_int("dose_number");
    $recommended_age = post_int("recommended_age_days");
    $minimum_interval = post_int("minimum_interval_days");
    $source = post_string("clinical_source", 255);

    if ($vaccine_id <= 0 || $dose_number <= 0) {
        $message = "Vaccine and dose number are required.";
    } else {
        $stmt = $conn->prepare(
            "INSERT INTO vaccine_doses
             (vaccine_id, dose_number, recommended_age_days, minimum_interval_days, clinical_source)
             VALUES (?, ?, NULLIF(?, 0), NULLIF(?, 0), ?)
             ON DUPLICATE KEY UPDATE
             recommended_age_days = VALUES(recommended_age_days),
             minimum_interval_days = VALUES(minimum_interval_days),
             clinical_source = VALUES(clinical_source),
             status = 'Active'"
        );
        $stmt->bind_param(
            "iiiis",
            $vaccine_id,
            $dose_number,
            $recommended_age,
            $minimum_interval,
            $source
        );
        $saved = $stmt->execute();
        $stmt->close();
        $message = $saved ? "Dose definition saved." : "Unable to save dose definition.";
        if ($saved) {
            audit($conn, $admin_id, "vaccine_dose.saved", "vaccine", $vaccine_id);
        }
    }
}

$vaccines = $conn->query("SELECT id, vaccine_name FROM vaccines ORDER BY vaccine_name");
$doses = $conn->query(
    "SELECT d.id, v.vaccine_name, d.dose_number, d.recommended_age_days,
            d.minimum_interval_days, d.clinical_source, d.status
     FROM vaccine_doses d JOIN vaccines v ON v.id = d.vaccine_id
     ORDER BY v.vaccine_name, d.dose_number"
);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Vaccine Doses | ImmuniCare</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="dashboard-body">
<main class="dashboard-main">
    <section class="dashboard-content">
        <h1>Vaccine dose definitions</h1>
        <?php if ($message !== ""): ?><div class="appointment-message"><?php echo e($message); ?></div><?php endif; ?>
        <div class="dashboard-card">
            <form method="POST">
                <?php echo csrf_field(); ?>
                <label for="vaccine_id">Vaccine</label>
                <select id="vaccine_id" name="vaccine_id" required>
                    <?php while ($vaccine = $vaccines->fetch_assoc()): ?>
                        <option value="<?php echo (int)$vaccine["id"]; ?>"><?php echo e($vaccine["vaccine_name"]); ?></option>
                    <?php endwhile; ?>
                </select>
                <label for="dose_number">Dose number</label>
                <input id="dose_number" type="number" min="1" name="dose_number" required>
                <label for="recommended_age_days">Recommended age (days)</label>
                <input id="recommended_age_days" type="number" min="0" name="recommended_age_days">
                <label for="minimum_interval_days">Minimum interval (days)</label>
                <input id="minimum_interval_days" type="number" min="0" name="minimum_interval_days">
                <label for="clinical_source">Clinical source/version</label>
                <input id="clinical_source" name="clinical_source">
                <button type="submit">Save dose</button>
            </form>
        </div>
        <div class="users-card">
            <table class="users-table">
                <thead><tr><th>Vaccine</th><th>Dose</th><th>Age days</th><th>Interval days</th><th>Source</th><th>Status</th></tr></thead>
                <tbody>
                <?php while ($dose = $doses->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo e($dose["vaccine_name"]); ?></td>
                        <td><?php echo (int)$dose["dose_number"]; ?></td>
                        <td><?php echo e($dose["recommended_age_days"]); ?></td>
                        <td><?php echo e($dose["minimum_interval_days"]); ?></td>
                        <td><?php echo e($dose["clinical_source"]); ?></td>
                        <td><?php echo e($dose["status"]); ?></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>
</body>
</html>
