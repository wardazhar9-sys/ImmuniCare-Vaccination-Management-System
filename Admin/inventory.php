<?php

require_once "../includes/app.php";

$admin = require_role($conn, "admin");
$admin_id = (int)$admin["id"];
$inventory_flash = flash_get();
$message = $inventory_flash["message"];
$message_type = $inventory_flash["type"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verify_csrf();
    $hospital_id = post_int("hospital_id");
    $vaccine_id = post_int("vaccine_id");
    $quantity = post_int("quantity");
    $reorder = post_int("reorder_level");

    if (
        $hospital_id <= 0 || $vaccine_id <= 0 ||
        $quantity < 0 || $quantity > 1000000 ||
        $reorder < 0 || $reorder > 1000000
    ) {
        flash_set("Enter valid inventory values.", "error");
        redirect_to("inventory.php");
    }

    mysqli_begin_transaction($conn);
    $current_stmt = $conn->prepare(
        "SELECT quantity
         FROM hospital_inventory
         WHERE hospital_id = ? AND vaccine_id = ?
         FOR UPDATE"
    );
    $current_stmt->bind_param("ii", $hospital_id, $vaccine_id);
    $current_stmt->execute();
    $current = $current_stmt->get_result()->fetch_assoc();
    $current_stmt->close();
    $old_quantity = $current ? (int)$current["quantity"] : 0;

    $stmt = $conn->prepare(
        "INSERT INTO hospital_inventory (hospital_id, vaccine_id, quantity, reorder_level)
         VALUES (?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE quantity = VALUES(quantity), reorder_level = VALUES(reorder_level)"
    );
    $stmt->bind_param("iiii", $hospital_id, $vaccine_id, $quantity, $reorder);
    $saved = $stmt->execute();
    $stmt->close();

    $delta = $quantity - $old_quantity;
    if ($saved && $delta !== 0) {
        $reason = "Admin adjustment";
        $ledger = $conn->prepare(
            "INSERT INTO inventory_transactions
             (hospital_id, vaccine_id, actor_user_id, quantity_delta,
              quantity_after, reason)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $ledger->bind_param(
            "iiiiis",
            $hospital_id,
            $vaccine_id,
            $admin_id,
            $delta,
            $quantity,
            $reason
        );
        $saved = $ledger->execute();
        $ledger->close();
    }

    if ($saved) {
        audit($conn, $admin_id, "inventory.updated", "hospital", $hospital_id, ["vaccine_id" => $vaccine_id]);
        mysqli_commit($conn);
    } else {
        mysqli_rollback($conn);
        flash_set("Unable to save inventory.", "error");
    }
    redirect_to("inventory.php");
}

$hospitals = $conn->query("SELECT id, hospital_name FROM hospitals ORDER BY hospital_name");
$vaccines = $conn->query("SELECT id, vaccine_name FROM vaccines ORDER BY vaccine_name");
$inventory = $conn->query(
    "SELECT i.quantity, i.reorder_level, h.hospital_name, v.vaccine_name,
            CASE WHEN i.quantity <= i.reorder_level
                 THEN 'Reorder required' ELSE 'Sufficient' END AS stock_status
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
    <?php if ($message !== ""): ?><div class="appointment-message <?php echo e($message_type); ?>"><?php echo e($message); ?></div><?php endif; ?>
    <div class="dashboard-card">
        <form method="POST" class="admin-tool-form">
            <?php echo csrf_field(); ?>
            <div class="tool-field"><label for="hospital_id">Hospital</label><select id="hospital_id" name="hospital_id" required><?php while ($row = $hospitals->fetch_assoc()): ?><option value="<?php echo (int)$row["id"]; ?>"><?php echo e($row["hospital_name"]); ?></option><?php endwhile; ?></select></div>
            <div class="tool-field"><label for="vaccine_id">Vaccine</label><select id="vaccine_id" name="vaccine_id" required><?php while ($row = $vaccines->fetch_assoc()): ?><option value="<?php echo (int)$row["id"]; ?>"><?php echo e($row["vaccine_name"]); ?></option><?php endwhile; ?></select></div>
            <div class="tool-field"><label for="quantity">Quantity</label><input id="quantity" type="number" min="0" max="1000000" name="quantity" required></div>
            <div class="tool-field"><label for="reorder_level">Reorder level</label><input id="reorder_level" type="number" min="0" max="1000000" name="reorder_level" required></div>
            <button type="submit">Save inventory</button>
        </form>
    </div>
    <div class="users-card"><table class="users-table"><thead><tr><th>Hospital</th><th>Vaccine</th><th>Quantity</th><th>Reorder level</th><th>Status</th></tr></thead><tbody>
        <?php while ($row = $inventory->fetch_assoc()): ?><tr><td><?php echo e($row["hospital_name"]); ?></td><td><?php echo e($row["vaccine_name"]); ?></td><td><?php echo (int)$row["quantity"]; ?></td><td><?php echo (int)$row["reorder_level"]; ?></td><td><?php echo e($row["stock_status"]); ?></td></tr><?php endwhile; ?>
    </tbody></table></div>
</section></main>
</div>
</body>
</html>
