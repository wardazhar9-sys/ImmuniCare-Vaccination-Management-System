<?php

require_once "../includes/app.php";

$admin = require_role($conn, "admin");
$admin_id = (int)$admin["id"];

$query = "SELECT c.child_name, u.name AS parent_name,
                 v.vaccine_name, h.hospital_name,
                 b.booking_date, b.booking_time, b.status
          FROM bookings b
          JOIN children c ON c.id = b.child_id
          JOIN users u ON u.id = b.parent_id
          JOIN vaccines v ON v.id = b.vaccine_id
          JOIN hospitals h ON h.id = b.hospital_id
          ORDER BY b.booking_date DESC, b.booking_time DESC";
$result = $conn->query($query);

if (($_GET["format"] ?? "") === "csv") {
    audit($conn, $admin_id, "report.exported", "booking", null, ["format" => "csv"]);
    header("Content-Type: text/csv; charset=utf-8");
    header("Content-Disposition: attachment; filename=immunicare-bookings.csv");
    $out = fopen("php://output", "w");
    fputcsv($out, ["Child", "Parent", "Vaccine", "Hospital", "Date", "Time", "Status"]);
    while ($row = $result->fetch_assoc()) {
        fputcsv($out, [
            $row["child_name"],
            $row["parent_name"],
            $row["vaccine_name"],
            $row["hospital_name"],
            $row["booking_date"],
            $row["booking_time"],
            $row["status"]
        ]);
    }
    fclose($out);
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Reports | ImmuniCare</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="dashboard-body">
<div class="dashboard-layout">
<?php include "sidebar.php"; ?>
<main class="dashboard-main">
    <section class="dashboard-content">
        <div class="section-heading">
            <h1>Reports</h1>
            <p>Export appointment activity for review.</p>
        </div>
        <a class="dashboard-primary-btn" href="reports.php?format=csv">Download bookings CSV</a>
    </section>
</main>
</div>
</body>
</html>
