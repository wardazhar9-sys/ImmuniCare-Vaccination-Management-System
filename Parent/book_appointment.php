<?php
require_once "../includes/app.php";
require_once "../includes/workflows.php";

$user = require_role($conn, "parent");
$parent_id = (int)$user["id"];
$selected_vaccine_id = (int)($_GET["vaccine_id"] ?? 0);
$selected_child_id = (int)($_GET["child_id"] ?? $_POST["child_id"] ?? 0);
$selected_vaccine_dose_id = (int)($_GET["vaccine_dose_id"] ?? $_POST["vaccine_dose_id"] ?? 0);
$message = "";
$message_type = "";

if (isset($_POST["book_appointment"])) {
    verify_csrf();
    $child_id = post_int("child_id");
    $vaccine_id = post_int("vaccine_id");
    $slot_id = post_int("slot_id");
    $vaccine_dose_id = post_int("vaccine_dose_id");

    $slot_stmt = $conn->prepare(
        "SELECT s.slot_date, s.slot_time, s.hospital_id
         FROM hospital_slots s JOIN hospitals h ON h.id = s.hospital_id
         WHERE s.id = ? AND s.status = 'Open' AND s.booked_count < s.capacity
           AND h.status = 'Active' AND s.slot_date >= CURDATE()"
    );
    $slot_stmt->bind_param("i", $slot_id);
    $slot_stmt->execute();
    $slot = $slot_stmt->get_result()->fetch_assoc();
    $slot_stmt->close();

    if (!$slot) {
        $message = "That slot is no longer available. Choose another slot.";
        $message_type = "error";
    } else {
        $result = create_booking_workflow(
            $conn,
            $parent_id,
            $child_id,
            $vaccine_id,
            (int)$slot["hospital_id"],
            $slot["slot_date"],
            substr($slot["slot_time"], 0, 5),
            $slot_id,
            $vaccine_dose_id
        );
        $message = $result["ok"]
            ? "Appointment booked and sent for hospital approval."
            : $result["error"];
        $message_type = $result["ok"] ? "success" : "error";
    }
}

$children_stmt = $conn->prepare(
    "SELECT id, child_name FROM children
     WHERE parent_id = ? AND archived_at IS NULL ORDER BY child_name"
);
$children_stmt->bind_param("i", $parent_id);
$children_stmt->execute();
$children_result = $children_stmt->get_result();

$vaccines_result = $conn->query(
    "SELECT id, vaccine_name FROM vaccines
     WHERE availability = 'Available' ORDER BY vaccine_name"
);
$eligible_vaccines = [];
if ($selected_child_id > 0) {
    while ($vaccine = $vaccines_result->fetch_assoc()) {
        $dose = next_eligible_vaccine_dose(
            $conn,
            $selected_child_id,
            (int)$vaccine["id"]
        );
        if ($dose) {
            $vaccine["dose"] = $dose;
            $eligible_vaccines[] = $vaccine;
        }
    }
}

$slots_result = $conn->query(
    "SELECT s.id, s.slot_date, s.slot_time, s.capacity, s.booked_count,
            h.hospital_name, h.city
     FROM hospital_slots s JOIN hospitals h ON h.id = s.hospital_id
     WHERE s.status = 'Open' AND s.booked_count < s.capacity
       AND h.status = 'Active' AND s.slot_date >= CURDATE()
     ORDER BY s.slot_date, s.slot_time, h.hospital_name"
);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment | ImmuniCare</title>

    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>

<div class="parent-dashboard">

    <!-- ================= SIDEBAR ================= -->
<?php include "sidebar.php"; ?>


    <!-- ================= MAIN CONTENT ================= -->

    <main class="dashboard-main">


        <!-- ================= HEADER ================= -->

        <?php include "../includes/portal_header.php"; ?>


        <!-- ================= BOOKING CONTENT ================= -->

        <section class="dashboard-content">


            <div class="section-heading">

                <div>

                    <h2>
                        Schedule an Appointment
                    </h2>

                    <p>
                        Select your child, vaccine, hospital and preferred appointment time.
                    </p>

                </div>

            </div>


            <?php if ($message != ""): ?>

                <div class="appointment-message <?php echo $message_type; ?>">

                    <?php echo htmlspecialchars($message); ?>

                </div>

            <?php endif; ?>


            <div class="booking-card">

                <form method="POST" class="booking-form">
                    <?php echo csrf_field(); ?>


                    <!-- CHILD -->

                    <div class="form-group">

                        <label for="child_id">
                            Select Child
                        </label>

                        <select
                            name="child_id"
                            id="child_id"
                            required
                            onchange="if (this.value) window.location='book_appointment.php?child_id=' + encodeURIComponent(this.value);"
                        >

                            <option value="">
                                Select Child
                            </option>

                            <?php while ($child = mysqli_fetch_assoc($children_result)): ?>

                                <option
                                    value="<?php echo $child["id"]; ?>"
                                    <?php echo ((int)$child["id"] === $selected_child_id) ? "selected" : ""; ?>
                                >

                                    <?php echo htmlspecialchars($child["child_name"]); ?>

                                </option>

                            <?php endwhile; ?>

                        </select>

                    </div>


                    <!-- VACCINE -->

                    <div class="form-group">

                        <label for="vaccine_id">
                            Select Vaccine
                        </label>

                        <select
                            name="vaccine_id"
                            id="vaccine_id"
                            required
                            <?php echo $selected_child_id > 0 ? "" : "disabled"; ?>
                        >

                            <option value="">
                                <?php echo $selected_child_id > 0 ? "Select next eligible vaccine dose" : "Select a child first"; ?>
                            </option>

                            <?php foreach ($eligible_vaccines as $vaccine): ?>

                                <option
                                    value="<?php echo (int)$vaccine["id"]; ?>"
                                    data-dose-id="<?php echo (int)$vaccine["dose"]["id"]; ?>"
                                    <?php echo ($vaccine["id"] == $selected_vaccine_id) ? "selected" : ""; ?>>

                                    <?php echo htmlspecialchars($vaccine["vaccine_name"]); ?>
                                    - <?php echo htmlspecialchars(
                                        $vaccine["dose"]["dose_label"] ?: "Dose " . (int)$vaccine["dose"]["dose_number"]
                                    ); ?>

                                </option>

                            <?php endforeach; ?>

                        </select>
                        <small class="form-help">
                            Only the next eligible dose is shown. A later dose appears after the previous dose is recorded as Vaccinated and its age and minimum-interval rules are satisfied.
                        </small>
                        <input
                            type="hidden"
                            name="vaccine_dose_id"
                            id="vaccine_dose_id"
                            value="<?php echo $selected_vaccine_dose_id; ?>"
                        >

                    </div>


                    <!-- SLOT -->
                    <div class="form-group">
                        <label for="slot_id">Select Available Slot</label>
                        <select name="slot_id" id="slot_id" required>
                            <option value="">Select hospital, date and time</option>
                            <?php if (mysqli_num_rows($slots_result) === 0): ?>
                                <option value="" disabled>No hospital slots are available.</option>
                            <?php else: ?>
                                <?php while ($slot = mysqli_fetch_assoc($slots_result)): ?>
                                    <option value="<?php echo (int)$slot["id"]; ?>">
                                        <?php echo htmlspecialchars($slot["hospital_name"]); ?>
                                        - <?php echo htmlspecialchars($slot["city"]); ?>
                                        - <?php echo htmlspecialchars($slot["slot_date"]); ?>
                                        <?php echo date("h:i A", strtotime($slot["slot_time"])); ?>
                                        (<?php echo (int)$slot["capacity"] - (int)$slot["booked_count"]; ?> left)
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                        <small class="form-help">
                            Only open hospital slots can be booked.
                        </small>
                    </div>


                    <!-- SUBMIT -->

                    <div class="booking-form-actions">

                        <button
                            type="submit"
                            name="book_appointment"
                            class="dashboard-primary-btn"
                        >
                            Book Appointment
                        </button>

                    </div>


                </form>

            </div>


        </section>


    </main>

</div>

<script>
const vaccineSelect = document.getElementById("vaccine_id");
const doseInput = document.getElementById("vaccine_dose_id");
if (vaccineSelect && doseInput) {
    const syncDose = () => {
        const option = vaccineSelect.options[vaccineSelect.selectedIndex];
        doseInput.value = option?.dataset.doseId || "";
    };
    vaccineSelect.addEventListener("change", syncDose);
    syncDose();
}
</script>


</body>
</html>