<?php

require_once "../includes/app.php";

$admin = require_role($conn, "admin");
$admin_id = (int)$admin["id"];

$allowed_reports = [
    "bookings" => "Bookings",
    "vaccinations" => "Vaccination records",
    "inventory" => "Inventory balances",
    "inventory_transactions" => "Inventory transactions"
];
$report = (string)($_GET["report"] ?? "bookings");
if (!isset($allowed_reports[$report])) {
    $report = "bookings";
}

$from = trim((string)($_GET["from"] ?? ""));
$to = trim((string)($_GET["to"] ?? ""));
$hospital_id = (int)($_GET["hospital_id"] ?? 0);
$vaccine_id = (int)($_GET["vaccine_id"] ?? 0);
$dose_number = (int)($_GET["dose_number"] ?? 0);
$status = trim((string)($_GET["status"] ?? ""));

if ($from !== "" && !valid_date($from)) {
    $from = "";
}
if ($to !== "" && !valid_date($to)) {
    $to = "";
}
if ($from !== "" && $to !== "" && $from > $to) {
    [$from, $to] = [$to, $from];
}

$conditions = [];
$params = [];
$types = "";
$headers = [];
$rows = [];

$add_filter = static function (
    string $condition,
    string $type,
    $value
) use (&$conditions, &$types, &$params): void {
    $conditions[] = $condition;
    $types .= $type;
    $params[] = $value;
};

if ($report === "bookings") {
    $sql = "SELECT b.id, c.child_name, u.name AS parent_name,
                    v.vaccine_name,
                    COALESCE(d.dose_number, v.dose_number) AS dose_number,
                    h.hospital_name, b.booking_date, b.booking_time, b.status
             FROM bookings b
             JOIN children c ON c.id = b.child_id
             JOIN users u ON u.id = b.parent_id
             JOIN vaccines v ON v.id = b.vaccine_id
             LEFT JOIN vaccine_doses d ON d.id = b.vaccine_dose_id
             JOIN hospitals h ON h.id = b.hospital_id";
    $date_column = "b.booking_date";
    if ($from !== "") {
        $add_filter("$date_column >= ?", "s", $from);
    }
    if ($to !== "") {
        $add_filter("$date_column <= ?", "s", $to);
    }
    if ($hospital_id > 0) {
        $add_filter("b.hospital_id = ?", "i", $hospital_id);
    }
    if ($vaccine_id > 0) {
        $add_filter("b.vaccine_id = ?", "i", $vaccine_id);
    }
    if ($dose_number > 0) {
        $add_filter("COALESCE(d.dose_number, v.dose_number) = ?", "i", $dose_number);
    }
    if ($status !== "" && in_array($status, ["Pending", "Approved", "Rejected", "Completed", "Cancelled"], true)) {
        $add_filter("b.status = ?", "s", $status);
    }
    $headers = ["ID", "Child", "Parent", "Vaccine", "Dose", "Hospital", "Date", "Time", "Status"];
} elseif ($report === "vaccinations") {
    $sql = "SELECT vr.id, c.child_name, u.name AS parent_name,
                    v.vaccine_name,
                    COALESCE(d.dose_number, vr.dose_number) AS dose_number,
                    h.hospital_name, vr.vaccination_date, vr.status, vr.remarks
             FROM vaccination_records vr
             JOIN children c ON c.id = vr.child_id
             JOIN users u ON u.id = c.parent_id
             JOIN vaccines v ON v.id = vr.vaccine_id
             LEFT JOIN vaccine_doses d ON d.id = vr.vaccine_dose_id
             JOIN hospitals h ON h.id = vr.hospital_id";
    $date_column = "vr.vaccination_date";
    if ($from !== "") {
        $add_filter("$date_column >= ?", "s", $from);
    }
    if ($to !== "") {
        $add_filter("$date_column <= ?", "s", $to);
    }
    if ($hospital_id > 0) {
        $add_filter("vr.hospital_id = ?", "i", $hospital_id);
    }
    if ($vaccine_id > 0) {
        $add_filter("vr.vaccine_id = ?", "i", $vaccine_id);
    }
    if ($dose_number > 0) {
        $add_filter("COALESCE(d.dose_number, vr.dose_number) = ?", "i", $dose_number);
    }
    if ($status !== "" && in_array($status, ["Vaccinated", "Not Vaccinated"], true)) {
        $add_filter("vr.status = ?", "s", $status);
    }
    $headers = ["ID", "Child", "Parent", "Vaccine", "Dose", "Hospital", "Date", "Status", "Remarks"];
} elseif ($report === "inventory") {
    $sql = "SELECT h.hospital_name, v.vaccine_name, i.quantity,
                    i.reorder_level,
                    CASE WHEN i.quantity <= i.reorder_level
                         THEN 'Reorder required' ELSE 'Sufficient' END AS stock_status
             FROM hospital_inventory i
             JOIN hospitals h ON h.id = i.hospital_id
             JOIN vaccines v ON v.id = i.vaccine_id";
    if ($hospital_id > 0) {
        $add_filter("i.hospital_id = ?", "i", $hospital_id);
    }
    if ($vaccine_id > 0) {
        $add_filter("i.vaccine_id = ?", "i", $vaccine_id);
    }
    $headers = ["Hospital", "Vaccine", "Quantity", "Reorder level", "Status"];
} else {
    $sql = "SELECT t.created_at, h.hospital_name, v.vaccine_name,
                    t.quantity_delta, t.quantity_after, t.reason,
                    u.name AS actor_name, t.vaccination_record_id
             FROM inventory_transactions t
             JOIN hospitals h ON h.id = t.hospital_id
             JOIN vaccines v ON v.id = t.vaccine_id
             LEFT JOIN users u ON u.id = t.actor_user_id";
    if ($from !== "") {
        $add_filter("DATE(t.created_at) >= ?", "s", $from);
    }
    if ($to !== "") {
        $add_filter("DATE(t.created_at) <= ?", "s", $to);
    }
    if ($hospital_id > 0) {
        $add_filter("t.hospital_id = ?", "i", $hospital_id);
    }
    if ($vaccine_id > 0) {
        $add_filter("t.vaccine_id = ?", "i", $vaccine_id);
    }
    $headers = ["Created", "Hospital", "Vaccine", "Change", "Balance", "Reason", "Actor", "Record ID"];
}

if ($conditions) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}
$sql .= $report === "inventory"
    ? " ORDER BY h.hospital_name, v.vaccine_name"
    : " ORDER BY 1 DESC";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    exit("Unable to prepare report.");
}
if ($params) {
    $bind_values = [$types];
    foreach ($params as $index => &$param) {
        $bind_values[] = &$param;
    }
    call_user_func_array([$stmt, "bind_param"], $bind_values);
    unset($param);
}
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    if ($report === "bookings") {
        $rows[] = [
            $row["id"],
            $row["child_name"],
            $row["parent_name"],
            $row["vaccine_name"],
            "Dose " . (int)$row["dose_number"],
            $row["hospital_name"],
            $row["booking_date"],
            $row["booking_time"],
            $row["status"]
        ];
    } elseif ($report === "vaccinations") {
        $rows[] = [
            $row["id"],
            $row["child_name"],
            $row["parent_name"],
            $row["vaccine_name"],
            "Dose " . (int)$row["dose_number"],
            $row["hospital_name"],
            $row["vaccination_date"],
            $row["status"],
            $row["remarks"]
        ];
    } elseif ($report === "inventory") {
        $rows[] = [
            $row["hospital_name"],
            $row["vaccine_name"],
            $row["quantity"],
            $row["reorder_level"],
            $row["stock_status"]
        ];
    } else {
        $rows[] = [
            $row["created_at"],
            $row["hospital_name"],
            $row["vaccine_name"],
            $row["quantity_delta"],
            $row["quantity_after"],
            $row["reason"],
            $row["actor_name"] ?: "System",
            $row["vaccination_record_id"] ?: ""
        ];
    }
}
$stmt->close();

if (($_GET["format"] ?? "") === "csv") {
    $export_filters = json_encode([
        "from" => $from,
        "to" => $to,
        "hospital_id" => $hospital_id,
        "vaccine_id" => $vaccine_id,
        "dose_number" => $dose_number,
        "status" => $status
    ]);
    $export_stmt = $conn->prepare(
        "INSERT INTO report_exports
         (requested_by, report_type, format, filters, status)
         VALUES (?, ?, 'CSV', ?, 'Ready')"
    );
    $export_stmt->bind_param("iss", $admin_id, $report, $export_filters);
    $export_stmt->execute();
    $export_stmt->close();
    audit($conn, $admin_id, "report.exported", "report", null, [
        "report" => $report,
        "from" => $from,
        "to" => $to,
        "hospital_id" => $hospital_id,
        "vaccine_id" => $vaccine_id,
        "dose_number" => $dose_number,
        "status" => $status
    ]);
    header("Content-Type: text/csv; charset=utf-8");
    header("Content-Disposition: attachment; filename=immunicare-" . $report . ".csv");
    $out = fopen("php://output", "w");
    fputcsv($out, $headers);
    foreach ($rows as $row) {
        fputcsv($out, $row);
    }
    fclose($out);
    exit;
}

$hospitals = $conn->query("SELECT id, hospital_name FROM hospitals ORDER BY hospital_name");
$vaccines = $conn->query("SELECT id, vaccine_name FROM vaccines ORDER BY vaccine_name");
$status_options = $report === "bookings"
    ? ["Pending", "Approved", "Rejected", "Completed", "Cancelled"]
    : ($report === "vaccinations" ? ["Vaccinated", "Not Vaccinated"] : []);

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
<?php include "../includes/portal_header.php"; ?>
    <section class="dashboard-content">
        <div class="section-heading">
            <h1>Reports</h1>
            <p>Review and export bookings, vaccinations, and inventory activity.</p>
        </div>

        <div class="dashboard-card">
            <form method="GET" class="admin-tool-form">
                <div class="tool-field">
                    <label for="report">Report type</label>
                    <select id="report" name="report" onchange="this.form.submit()">
                        <?php foreach ($allowed_reports as $value => $label): ?>
                            <option value="<?php echo e($value); ?>" <?php echo $report === $value ? "selected" : ""; ?>>
                                <?php echo e($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="tool-field">
                    <label for="from">From</label>
                    <input id="from" type="date" name="from" value="<?php echo e($from); ?>">
                </div>
                <div class="tool-field">
                    <label for="to">To</label>
                    <input id="to" type="date" name="to" value="<?php echo e($to); ?>">
                </div>
                <div class="tool-field">
                    <label for="hospital_id">Hospital</label>
                    <select id="hospital_id" name="hospital_id">
                        <option value="0">All hospitals</option>
                        <?php while ($hospital = $hospitals->fetch_assoc()): ?>
                            <option value="<?php echo (int)$hospital["id"]; ?>" <?php echo $hospital_id === (int)$hospital["id"] ? "selected" : ""; ?>>
                                <?php echo e($hospital["hospital_name"]); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="tool-field">
                    <label for="vaccine_id">Vaccine</label>
                    <select id="vaccine_id" name="vaccine_id">
                        <option value="0">All vaccines</option>
                        <?php while ($vaccine = $vaccines->fetch_assoc()): ?>
                            <option value="<?php echo (int)$vaccine["id"]; ?>" <?php echo $vaccine_id === (int)$vaccine["id"] ? "selected" : ""; ?>>
                                <?php echo e($vaccine["vaccine_name"]); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <?php if (in_array($report, ["bookings", "vaccinations"], true)): ?>
                    <div class="tool-field">
                        <label for="dose_number">Dose number</label>
                        <input id="dose_number" type="number" name="dose_number" min="1" max="100" value="<?php echo $dose_number > 0 ? $dose_number : ""; ?>">
                    </div>
                <?php endif; ?>
                <?php if ($status_options): ?>
                    <div class="tool-field">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <option value="">All statuses</option>
                            <?php foreach ($status_options as $option): ?>
                                <option value="<?php echo e($option); ?>" <?php echo $status === $option ? "selected" : ""; ?>>
                                    <?php echo e($option); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
                <button type="submit">Apply filters</button>
                <a
                    class="dashboard-secondary-btn"
                    href="reports.php?<?php echo http_build_query(array_merge($_GET, ["format" => "csv", "report" => $report])); ?>"
                >
                    Download <?php echo e($allowed_reports[$report]); ?> CSV
                </a>
            </form>
        </div>

        <div class="dashboard-card">
            <h3><?php echo e($allowed_reports[$report]); ?></h3>
            <p><?php echo count($rows); ?> result(s) match the selected filters.</p>
            <div class="users-table-wrapper">
                <table class="users-table">
                    <thead>
                        <tr>
                            <?php foreach ($headers as $header): ?><th><?php echo e($header); ?></th><?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$rows): ?>
                            <tr><td colspan="<?php echo count($headers); ?>">No records match these filters.</td></tr>
                        <?php else: ?>
                            <?php foreach ($rows as $row): ?>
                                <tr>
                                    <?php foreach ($row as $cell): ?><td><?php echo e($cell); ?></td><?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</main>
</div>
</body>
</html>
