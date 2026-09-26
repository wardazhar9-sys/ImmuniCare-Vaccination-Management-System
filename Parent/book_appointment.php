<?php
require_once "../includes/app.php";
require_once "../includes/workflows.php";

$user = require_role($conn, "parent");
$parent_id = (int)$user["id"];
$selected_vaccine_id = (int)($_GET["vaccine_id"] ?? 0);
$message = "";
$message_type = "";

if (isset($_POST["book_appointment"])) {
    verify_csrf();
    $child_id = post_int("child_id");
    $vaccine_id = post_int("vaccine_id");
    $slot_id = post_int("slot_id");

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
            $slot_id
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
    "SELECT id, vaccine_name, dose_number FROM vaccines
     WHERE availability = 'Available' ORDER BY vaccine_name"
);

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

                        <select name="child_id" id="child_id" required>

                            <option value="">
                                Select Child
                            </option>

                            <?php while ($child = mysqli_fetch_assoc($children_result)): ?>

                                <option value="<?php echo $child["id"]; ?>">

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

                        <select name="vaccine_id" id="vaccine_id" required>

                            <option value="">
                                Select Vaccine
                            </option>

                            <?php while ($vaccine = mysqli_fetch_assoc($vaccines_result)): ?>

                                <option value="<?php echo $vaccine["id"]; ?>"
                                    <?php echo ($vaccine["id"] == $selected_vaccine_id) ? "selected" : ""; ?>>

                                    <?php echo htmlspecialchars($vaccine["vaccine_name"]); ?>
                                    - Dose <?php echo htmlspecialchars($vaccine["dose_number"]); ?>

                                </option>

                            <?php endwhile; ?>

                        </select>

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


</body>
</html>