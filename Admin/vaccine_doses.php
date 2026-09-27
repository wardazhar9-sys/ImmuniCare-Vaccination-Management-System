<?php

require_once "../includes/app.php";

$admin = require_role($conn, "admin");
$admin_id = (int)$admin["id"];
$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verify_csrf();
    $dose_id = post_int("dose_id");
    $vaccine_id = post_int("vaccine_id");
    $dose_number = post_int("dose_number");
    $dose_label = post_string("dose_label", 100);
    $recommended_age = post_int("recommended_age_days");
    $minimum_interval = post_int("minimum_interval_days");
    $catch_up_rule = post_string("catch_up_rule", 500);
    $source = post_string("clinical_source", 255);
    $source_version = post_string("source_version", 50);
    $dose_status = post_string("dose_status", 20);

    if (
        $vaccine_id <= 0 || $dose_number <= 0 || $dose_number > 100 ||
        $recommended_age < 0 || $recommended_age > 36500 ||
        $minimum_interval < 0 || $minimum_interval > 36500 ||
        !in_array($dose_status, ["Active", "Inactive"], true)
    ) {
        $message = "Enter valid dose information.";
    } else {
        if ($dose_id > 0) {
            $stmt = $conn->prepare(
                "UPDATE vaccine_doses
                 SET vaccine_id = ?, dose_number = ?, dose_label = NULLIF(?, ''),
                     recommended_age_days = NULLIF(?, 0),
                     minimum_interval_days = NULLIF(?, 0),
                     catch_up_rule = NULLIF(?, ''),
                     clinical_source = NULLIF(?, ''),
                     source_version = NULLIF(?, ''),
                     status = ?
                 WHERE id = ?"
            );
            $stmt->bind_param(
                "iisiissssi",
                $vaccine_id,
                $dose_number,
                $dose_label,
                $recommended_age,
                $minimum_interval,
                $catch_up_rule,
                $source,
                $source_version,
                $dose_status,
                $dose_id
            );
        } else {
            $stmt = $conn->prepare(
                "INSERT INTO vaccine_doses
                 (vaccine_id, dose_number, dose_label, recommended_age_days,
                  minimum_interval_days, catch_up_rule, clinical_source,
                  source_version, status)
                 VALUES (?, ?, NULLIF(?, ''), NULLIF(?, 0), NULLIF(?, 0),
                         NULLIF(?, ''), NULLIF(?, ''), NULLIF(?, ''), ?)
                 ON DUPLICATE KEY UPDATE
                     dose_label = VALUES(dose_label),
                     recommended_age_days = VALUES(recommended_age_days),
                     minimum_interval_days = VALUES(minimum_interval_days),
                     catch_up_rule = VALUES(catch_up_rule),
                     clinical_source = VALUES(clinical_source),
                     source_version = VALUES(source_version),
                     status = VALUES(status)"
            );
            $stmt->bind_param(
                "iisiissss",
                $vaccine_id,
                $dose_number,
                $dose_label,
                $recommended_age,
                $minimum_interval,
                $catch_up_rule,
                $source,
                $source_version,
                $dose_status
            );
        }
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
    "SELECT d.id, d.vaccine_id, v.vaccine_name, d.dose_number, d.dose_label,
            d.recommended_age_days, d.minimum_interval_days, d.catch_up_rule,
            d.clinical_source, d.source_version, d.status
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
<div class="dashboard-layout">
<?php include "sidebar.php"; ?>
<main class="dashboard-main">
<?php include "../includes/portal_header.php"; ?>
    <section class="dashboard-content">
        <h1>Vaccine dose definitions</h1>
        <?php if ($message !== ""): ?><div class="appointment-message"><?php echo e($message); ?></div><?php endif; ?>
        <div class="dashboard-card">
            <form method="POST" class="admin-tool-form">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="dose_id" id="dose_id" value="0">
                <input type="hidden" name="save_vaccine_dose" value="1">
                <div class="tool-field"><label for="vaccine_id">Vaccine</label><select id="vaccine_id" name="vaccine_id" required>
                    <?php while ($vaccine = $vaccines->fetch_assoc()): ?><option value="<?php echo (int)$vaccine["id"]; ?>"><?php echo e($vaccine["vaccine_name"]); ?></option><?php endwhile; ?>
                </select></div>
                <div class="tool-field"><label for="dose_number">Dose number</label><input id="dose_number" type="number" min="1" max="100" name="dose_number" required></div>
                <div class="tool-field"><label for="dose_label">Display label</label><input id="dose_label" name="dose_label" maxlength="100" placeholder="Dose 1 or Booster"></div>
                <div class="tool-field"><label for="recommended_age_days">Recommended age (days)</label><input id="recommended_age_days" type="number" min="0" max="36500" name="recommended_age_days"></div>
                <div class="tool-field"><label for="minimum_interval_days">Minimum interval (days)</label><input id="minimum_interval_days" type="number" min="0" max="36500" name="minimum_interval_days"></div>
                <div class="tool-field"><label for="catch_up_rule">Catch-up rule</label><input id="catch_up_rule" name="catch_up_rule" maxlength="500"></div>
                <div class="tool-field"><label for="clinical_source">Clinical source/version</label><input id="clinical_source" name="clinical_source" maxlength="255"></div>
                <div class="tool-field"><label for="source_version">Source version</label><input id="source_version" name="source_version" maxlength="50"></div>
                <div class="tool-field"><label for="dose_status">Status</label><select id="dose_status" name="dose_status" required>
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                </select></div>
                <button type="submit">Save dose</button>
                <button type="button" class="btn-secondary" onclick="resetDoseForm()">New dose</button>
            </form>
        </div>
        <div class="users-card">
            <table class="users-table">
                <thead><tr><th>Vaccine</th><th>Dose</th><th>Age days</th><th>Interval days</th><th>Catch-up</th><th>Source</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                <?php while ($dose = $doses->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo e($dose["vaccine_name"]); ?></td>
                        <td><?php echo e($dose["dose_label"] ?: "Dose " . (int)$dose["dose_number"]); ?> <small>(<?php echo (int)$dose["dose_number"]; ?>)</small></td>
                        <td><?php echo e($dose["recommended_age_days"]); ?></td>
                        <td><?php echo e($dose["minimum_interval_days"]); ?></td>
                        <td><?php echo e($dose["catch_up_rule"]); ?></td>
                        <td><?php echo e($dose["clinical_source"]); ?></td>
                        <td><?php echo e($dose["status"]); ?></td>
                        <td>
                            <button
                                type="button"
                                class="user-action-edit"
                                onclick="editDose(
                                    <?php echo (int)$dose["id"]; ?>,
                                    <?php echo (int)$dose["vaccine_id"]; ?>,
                                    <?php echo (int)$dose["dose_number"]; ?>,
                                    <?php echo htmlspecialchars(json_encode($dose["dose_label"] ?? ""), ENT_QUOTES, "UTF-8"); ?>,
                                    <?php echo $dose["recommended_age_days"] === null ? 0 : (int)$dose["recommended_age_days"]; ?>,
                                    <?php echo $dose["minimum_interval_days"] === null ? 0 : (int)$dose["minimum_interval_days"]; ?>,
                                    <?php echo htmlspecialchars(json_encode($dose["catch_up_rule"] ?? ""), ENT_QUOTES, "UTF-8"); ?>,
                                    <?php echo htmlspecialchars(json_encode($dose["clinical_source"] ?? ""), ENT_QUOTES, "UTF-8"); ?>,
                                    <?php echo htmlspecialchars(json_encode($dose["source_version"] ?? ""), ENT_QUOTES, "UTF-8"); ?>,
                                    <?php echo htmlspecialchars(json_encode($dose["status"]), ENT_QUOTES, "UTF-8"); ?>
                                )"
                            >Edit</button>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>
</div>
<script>
function editDose(id, vaccineId, doseNumber, doseLabel, recommendedAge, minimumInterval, catchUpRule, source, sourceVersion, status) {
    document.getElementById("dose_id").value = id;
    document.getElementById("vaccine_id").value = vaccineId;
    document.getElementById("dose_number").value = doseNumber;
    document.getElementById("dose_label").value = doseLabel || "";
    document.getElementById("recommended_age_days").value = recommendedAge || "";
    document.getElementById("minimum_interval_days").value = minimumInterval || "";
    document.getElementById("catch_up_rule").value = catchUpRule || "";
    document.getElementById("clinical_source").value = source || "";
    document.getElementById("source_version").value = sourceVersion || "";
    document.getElementById("dose_status").value = status || "Active";
    window.scrollTo({ top: 0, behavior: "smooth" });
}

function resetDoseForm() {
    document.getElementById("dose_id").value = "0";
    document.querySelector("form.admin-tool-form").reset();
    document.getElementById("dose_id").value = "0";
}
</script>
</body>
</html>
