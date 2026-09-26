<?php

require_once "../includes/app.php";

$admin = require_role($conn, "admin");
$search = trim((string)($_GET["search"] ?? ""));

$sql = "SELECT vr.id, c.child_name, u.name AS parent_name,
               v.vaccine_name, vr.dose_number, h.hospital_name,
               vr.vaccination_date, vr.status, vr.remarks
        FROM vaccination_records vr
        JOIN children c ON c.id = vr.child_id
        JOIN users u ON u.id = c.parent_id
        JOIN vaccines v ON v.id = vr.vaccine_id
        JOIN hospitals h ON h.id = vr.hospital_id
        WHERE 1=1";

if ($search !== "") {
    $sql .= " AND (c.child_name LIKE ? OR u.name LIKE ? OR v.vaccine_name LIKE ?)";
    $value = "%$search%";
    $stmt = $conn->prepare($sql . " ORDER BY vr.vaccination_date DESC");
    $stmt->bind_param("sss", $value, $value, $value);
} else {
    $stmt = $conn->prepare($sql . " ORDER BY vr.vaccination_date DESC");
}

$stmt->execute();
$records = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Vaccination Records | ImmuniCare</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="dashboard-body">
<div class="dashboard-layout">
    <aside class="dashboard-sidebar">
        <div class="sidebar-brand"><img src="../assets/images/immunicare-logo-sidebar.svg" alt="ImmuniCare" class="sidebar-brand-image"></div>
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="sidebar-link"><span class="sidebar-icon">⌂</span><span>Dashboard</span></a>
            <a href="users.php" class="sidebar-link"><span class="sidebar-icon">♧</span><span>Users</span></a>
            <a href="children.php" class="sidebar-link"><span class="sidebar-icon">♙</span><span>Children</span></a>
            <a href="hospitals.php" class="sidebar-link"><span class="sidebar-icon">♜</span><span>Hospitals</span></a>
            <a href="vaccines.php" class="sidebar-link"><span class="sidebar-icon">✚</span><span>Vaccines</span></a>
            <a href="bookings.php" class="sidebar-link"><span class="sidebar-icon">▤</span><span>Bookings</span></a>
            <a href="schedules.php" class="sidebar-link"><span class="sidebar-icon">▣</span><span>Schedules</span></a>
            <a href="vaccination_records.php" class="sidebar-link active"><span class="sidebar-icon">✓</span><span>Records</span></a>
            <a href="profile.php" class="sidebar-link"><span class="sidebar-icon">◯</span><span>Profile</span></a>
        </nav>
        <div class="sidebar-bottom"><a href="logout.php" class="logout-link">Logout</a></div>
    </aside>
    <main class="dashboard-main">
        <header class="dashboard-header">
            <div class="header-page-title"><h1>Vaccination Records</h1><p>Audited vaccination history across the system.</p></div>
        </header>
        <section class="dashboard-content">
            <form class="users-search-form" method="GET">
                <label for="search">Search</label>
                <input id="search" name="search" value="<?php echo e($search); ?>" placeholder="Child, parent or vaccine">
                <button type="submit">Search</button>
            </form>
            <div class="users-card">
                <div class="users-table-wrapper">
                    <table class="users-table">
                        <thead><tr><th>Child</th><th>Parent</th><th>Vaccine</th><th>Hospital</th><th>Date</th><th>Status</th><th>Remarks</th></tr></thead>
                        <tbody>
                        <?php while ($record = $records->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo e($record["child_name"]); ?></td>
                                <td><?php echo e($record["parent_name"]); ?></td>
                                <td><?php echo e($record["vaccine_name"]); ?> / Dose <?php echo (int)$record["dose_number"]; ?></td>
                                <td><?php echo e($record["hospital_name"]); ?></td>
                                <td><?php echo e(format_date_value($record["vaccination_date"])); ?></td>
                                <td><?php echo e($record["status"]); ?></td>
                                <td><?php echo e($record["remarks"]); ?></td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </main>
</div>
</body>
</html>
