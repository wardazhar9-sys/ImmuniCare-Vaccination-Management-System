<?php

require_once "../includes/app.php";

$admin = require_role($conn, "admin");
$admin_id = (int)$admin["id"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verify_csrf();
    $hospital_id = post_int("hospital_id");
    $vaccine_id = post_int("vaccine_id");
    $quantity = max(0, post_int("quantity"));
    $reorder = max(0, post_int("reorder_level"));

    $stmt = $conn->prepare(
        "INSERT INTO hospital_inventory (hospital_id, vaccine_id, quantity, reorder_level)
         VALUES (?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE quantity = VALUES(quantity), reorder_level = VALUES(reorder_level)"
    );
    $stmt->bind_param("iiii", $hospital_id, $vaccine_id, $quantity, $reorder);
    $stmt->execute();
    $stmt->close();
    audit($conn, $admin_id, "inventory.updated", "hospital", $hospital_id, ["vaccine_id" => $vaccine_id]);
    redirect_to("inventory.php");
}

$hospitals = $conn->query("SELECT id, hospital_name FROM hospitals ORDER BY hospital_name");
$vaccines = $conn->query("SELECT id, vaccine_name FROM vaccines ORDER BY vaccine_name");
$inventory = $conn->query(
    "SELECT i.quantity, i.reorder_level, h.hospital_name, v.vaccine_name
     FROM hospital_inventory i
     JOIN hospitals h ON h.id = i.hospital_id
     JOIN vaccines v ON v.id = i.vaccine_id
     ORDER BY h.hospital_name, v.vaccine_name"
);
?>
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Inventory | ImmuniCare</title><link rel="stylesheet" href="../assets/css/style.css"></head>
<body class="dashboard-body">
<div class="dashboard-layout">
<?php include "sidebar.php"; ?>
<main class="dashboard-main">
<?php include "../includes/portal_header.php"; ?><section class="dashboard-content">
    <h1>Hospital vaccine inventory</h1>
    <div class="dashboard-card">
        <form method="POST">
            <?php echo csrf_field(); ?>
            <label for="hospital_id">Hospital</label>
            <select id="hospital_id" name="hospital_id" required><?php while ($row = $hospitals->fetch_assoc()): ?><option value="<?php echo (int)$row["id"]; ?>"><?php echo e($row["hospital_name"]); ?></option><?php endwhile; ?></select>
            <label for="vaccine_id">Vaccine</label>
            <select id="vaccine_id" name="vaccine_id" required><?php while ($row = $vaccines->fetch_assoc()): ?><option value="<?php echo (int)$row["id"]; ?>"><?php echo e($row["vaccine_name"]); ?></option><?php endwhile; ?></select>
            <label for="quantity">Quantity</label><input id="quantity" type="number" min="0" name="quantity" required>
            <label for="reorder_level">Reorder level</label><input id="reorder_level" type="number" min="0" name="reorder_level" required>
            <button type="submit">Save inventory</button>
        </form>
    </div>
    <div class="users-card"><table class="users-table"><thead><tr><th>Hospital</th><th>Vaccine</th><th>Quantity</th><th>Reorder level</th></tr></thead><tbody>
        <?php while ($row = $inventory->fetch_assoc()): ?><tr><td><?php echo e($row["hospital_name"]); ?></td><td><?php echo e($row["vaccine_name"]); ?></td><td><?php echo (int)$row["quantity"]; ?></td><td><?php echo (int)$row["reorder_level"]; ?></td></tr><?php endwhile; ?>
    </tbody></table></div>
</section></main>
</div>
</body>
</html>
