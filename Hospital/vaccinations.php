<?php
require_once "../includes/app.php";

$user = require_role($conn, "hospital");
$user_id = (int)$user["id"];
$name = $user["name"];

$hospital_stmt = $conn->prepare(
    "SELECT id, hospital_name FROM hospitals
     WHERE user_id = ? AND status = 'Active' LIMIT 1"
);
$hospital_stmt->bind_param("i", $user_id);
$hospital_stmt->execute();
$hospital = $hospital_stmt->get_result()->fetch_assoc();
$hospital_stmt->close();

if (!$hospital) {
    http_response_code(403);
    exit("Hospital approval is required before using this portal.");
}

$hospital_id = (int)$hospital["id"];
$hospital_name = $hospital["hospital_name"];
$success_message = "";
$error_message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verify_csrf();
    $booking_id = post_int("booking_id");
    $vaccination_date = post_string("vaccination_date", 10);
    $status = post_string("status", 20);
    $remarks = post_string("remarks", 500);

    if (
        $booking_id <= 0 || !valid_date($vaccination_date) ||
        $vaccination_date > date("Y-m-d") ||
        !in_array($status, ["Vaccinated", "Not Vaccinated"], true)
    ) {
        $error_message = "Enter a valid vaccination date and status.";
    } else {
        mysqli_begin_transaction($conn);
        $stmt = $conn->prepare(
            "SELECT b.parent_id, b.child_id, b.vaccine_id, v.dose_number,
                    c.child_name, v.vaccine_name, vs.id AS schedule_id
             FROM bookings b
             JOIN children c ON c.id = b.child_id
             JOIN vaccines v ON v.id = b.vaccine_id
             JOIN vaccination_schedules vs ON vs.booking_id = b.id
             WHERE b.id = ? AND b.hospital_id = ? AND b.status = 'Approved'
               AND vs.status = 'Scheduled' LIMIT 1"
        );
        $stmt->bind_param("ii", $booking_id, $hospital_id);
        $stmt->execute();
        $booking = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$booking) {
            mysqli_rollback($conn);
            $error_message = "This appointment is not ready for recording.";
        } else {
            $stmt = $conn->prepare(
                "SELECT id FROM vaccination_records WHERE booking_id = ? LIMIT 1"
            );
            $stmt->bind_param("i", $booking_id);
            $stmt->execute();
            $existing = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($existing) {
                mysqli_rollback($conn);
                $error_message = "This vaccination has already been recorded.";
            } else {
                $record_stmt = $conn->prepare(
                    "INSERT INTO vaccination_records
                     (booking_id, schedule_id, child_id, vaccine_id, hospital_id,
                      dose_number, vaccination_date, status, remarks, recorded_by)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                );
                $record_stmt->bind_param(
                    "iiiiiisssi",
                    $booking_id,
                    $booking["schedule_id"],
                    $booking["child_id"],
                    $booking["vaccine_id"],
                    $hospital_id,
                    $booking["dose_number"],
                    $vaccination_date,
                    $status,
                    $remarks,
                    $user_id
                );
                $saved = $record_stmt->execute();
                $record_id = $record_stmt->insert_id;
                $record_stmt->close();

                if ($saved) {
                    $schedule_status = $status === "Vaccinated" ? "Completed" : "Missed";
                    $booking_status = $status === "Vaccinated" ? "Completed" : "Cancelled";
                    $update = $conn->prepare(
                        "UPDATE vaccination_schedules SET status = ? WHERE id = ?"
                    );
                    $update->bind_param("si", $schedule_status, $booking["schedule_id"]);
                    $saved = $update->execute();
                    $update->close();

                    $update = $conn->prepare(
                        "UPDATE bookings SET status = ?, updated_at = NOW()
                         WHERE id = ? AND hospital_id = ?"
                    );
                    $update->bind_param("sii", $booking_status, $booking_id, $hospital_id);
                    $saved = $saved && $update->execute();
                    $update->close();
                }

                if ($saved) {
                    $saved = notify_user(
                        $conn,
                        (int)$booking["parent_id"],
                        $status === "Vaccinated" ? "Vaccination recorded" : "Vaccination missed",
                        "The vaccination for " . $booking["child_name"] . " (" . $booking["vaccine_name"] . ") was recorded as " . $status . ".",
                        "vaccination",
                        "Parent/vaccination_history.php"
                    );
                }

                if ($saved) {
                    audit($conn, $user_id, "vaccination.recorded", "vaccination_record", $record_id, ["status" => $status]);
                    mysqli_commit($conn);
                    $success_message = "Vaccination record saved.";
                } else {
                    mysqli_rollback($conn);
                    $error_message = "Unable to save the vaccination record.";
                }
            }
        }
    }
}

$vaccinations_stmt = $conn->prepare(
    "SELECT b.id AS booking_id, b.status AS booking_status,
            c.child_name, v.vaccine_name, v.dose_number,
            vs.scheduled_date, vs.scheduled_time, vs.status AS schedule_status,
            vr.id AS record_id, vr.status AS record_status, vr.vaccination_date
     FROM bookings b
     JOIN children c ON c.id = b.child_id
     JOIN vaccines v ON v.id = b.vaccine_id
     JOIN vaccination_schedules vs ON vs.booking_id = b.id
     LEFT JOIN vaccination_records vr ON vr.booking_id = b.id
     WHERE b.hospital_id = ? AND b.status IN ('Approved', 'Completed', 'Cancelled')
     ORDER BY vs.scheduled_date, vs.scheduled_time"
);
$vaccinations_stmt->bind_param("i", $hospital_id);
$vaccinations_stmt->execute();
$vaccinations_data = $vaccinations_stmt->get_result();

?>
<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Vaccinations | ImmuniCare</title>

    <link rel="stylesheet" href="../assets/css/style.css">

</head>


<body>

<div class="parent-dashboard">


    <!-- ================= SIDEBAR ================= -->
<?php include "sidebar.php"; ?>



    <!-- ================= MAIN CONTENT ================= -->

    <main class="dashboard-main">


        <!-- HEADER -->

        <?php include "../includes/portal_header.php"; ?>



        <!-- CONTENT -->

        <div class="dashboard-content">


            <div class="section-heading">

                <div>

                    <h2>Scheduled Vaccinations</h2>

                    <p>
                        Record vaccinations for appointments scheduled at
                        <?php echo htmlspecialchars($hospital_name); ?>.
                    </p>

                </div>

            </div>


            <?php if (!empty($success_message)): ?>

                <div class="dashboard-card" style="margin-bottom:20px; color:#15803D;">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>

            <?php endif; ?>


            <?php if (!empty($error_message)): ?>

                <div class="dashboard-card" style="margin-bottom:20px; color:#DC2626;">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>

            <?php endif; ?>


            <!-- VACCINATION LIST -->

            <div class="dashboard-card">


                <?php if (mysqli_num_rows($vaccinations_data) > 0): ?>


                    <div style="overflow-x:auto;">

                        <table class="hospital-vaccinations-table">

                            <thead>

                                <tr>

                                    <th style="text-align:left; padding:15px;">
                                        Child
                                    </th>

                                    <th style="text-align:left; padding:15px;">
                                        Vaccine
                                    </th>

                                    <th style="text-align:left; padding:15px;">
                                        Scheduled Date
                                    </th>

                                    <th style="text-align:left; padding:15px;">
                                        Scheduled Time
                                    </th>

                                    <th style="text-align:left; padding:15px;">
                                        Status
                                    </th>

                                    <th style="text-align:left; padding:15px;">
                                        Action
                                    </th>

                                </tr>

                            </thead>


                            <tbody>


                            <?php while ($vaccination = mysqli_fetch_assoc($vaccinations_data)): ?>

                                <?php
                                $has_record = !empty($vaccination["record_id"]);
                                $status_text = $has_record ? $vaccination["record_status"] : $vaccination["schedule_status"];
                                if ($status_text === "Vaccinated") {
                                    $status_class = "completed";
                                } elseif ($status_text === "Not Vaccinated") {
                                    $status_class = "cancelled";
                                } else {
                                    $status_class = strtolower(str_replace(" ", "-", $status_text));
                                }
                                ?>


                                <tr>


                                    <td style="padding:15px;">

                                        <?php echo htmlspecialchars($vaccination["child_name"]); ?>

                                    </td>


                                    <td style="padding:15px;">

                                        <?php echo htmlspecialchars($vaccination["vaccine_name"]); ?>

                                        <br>

                                        <small>
                                            Dose <?php echo htmlspecialchars($vaccination["dose_number"]); ?>
                                        </small>

                                    </td>


                                    <td style="padding:15px;">

                                        <?php
                                        echo date(
                                            "d M Y",
                                            strtotime($vaccination["scheduled_date"])
                                        );
                                        ?>

                                    </td>


                                    <td style="padding:15px;">

                                        <?php
                                        echo date(
                                            "h:i A",
                                            strtotime($vaccination["scheduled_time"])
                                        );
                                        ?>

                                    </td>


                                    <td style="padding:15px;">

                                        <span class="schedule-status <?php echo htmlspecialchars($status_class); ?>">
                                            <?php echo htmlspecialchars($status_text); ?>
                                        </span>

                                        <?php if ($has_record): ?>

                                            <br>

                                            <small>
                                                <?php
                                                echo date(
                                                    "d M Y",
                                                    strtotime($vaccination["vaccination_date"])
                                                );
                                                ?>
                                            </small>

                                        <?php endif; ?>

                                    </td>


                                    <td class="hospital-vaccination-action-cell">

                                        <?php if ($has_record): ?>

                                            <span class="history-status vaccinated">
                                                Completed
                                            </span>

                                        <?php else: ?>

                                            <form method="POST" class="hospital-vaccination-action-form">
                                                <?php echo csrf_field(); ?>

                                                <input
                                                    type="hidden"
                                                    name="booking_id"
                                                    value="<?php echo htmlspecialchars($vaccination["booking_id"]); ?>"
                                                >

                                                <input
                                                    type="date"
                                                    name="vaccination_date"
                                                    value="<?php echo date("Y-m-d"); ?>"
                                                    required
                                                >

                                                <select name="status" required>
                                                    <option value="Vaccinated">Vaccinated</option>
                                                    <option value="Not Vaccinated">Not Vaccinated</option>
                                                </select>

                                                <textarea
                                                    name="remarks"
                                                    rows="2"
                                                    placeholder="Remarks"
                                                ></textarea>

                                                <button type="submit">
                                                    Record Vaccination
                                                </button>

                                            </form>

                                        <?php endif; ?>

                                    </td>


                                </tr>


                            <?php endwhile; ?>


                            </tbody>

                        </table>

                    </div>


                <?php else: ?>


                    <div class="empty-dashboard-state">

                        <div class="empty-state-icon">
                            ✓
                        </div>


                        <h3>No Scheduled Vaccinations</h3>


                        <p>
                            There are currently no scheduled vaccinations
                            waiting to be recorded for this hospital.
                        </p>

                    </div>


                <?php endif; ?>


            </div>


        </div>


    </main>


</div>

<?php mysqli_stmt_close($vaccinations_stmt); ?>

</body>

</html>
