<?php
require_once "../includes/app.php";
$user = require_role($conn, "parent");
$name = $user["name"];
$parent_id = (int)$user["id"];

$stmt = $conn->prepare(
    "SELECT c.child_name, v.vaccine_name,
            COALESCE(d.dose_number, vs.dose_number) AS dose_number,
            vs.scheduled_date, vs.scheduled_time, vs.status
     FROM vaccination_schedules vs
     JOIN bookings b ON b.id = vs.booking_id
     JOIN children c ON c.id = vs.child_id
     JOIN vaccines v ON v.id = vs.vaccine_id
     LEFT JOIN vaccine_doses d ON d.id = vs.vaccine_dose_id
     WHERE c.parent_id = ? ORDER BY vs.scheduled_date, vs.scheduled_time"
);
$stmt->bind_param("i", $parent_id);
$stmt->execute();
$schedule_result = $stmt->get_result();

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Vaccination Schedule | ImmuniCare</title>

    <link rel="stylesheet" href="../assets/css/style.css">

</head>

<body>

<div class="parent-dashboard">

    <!-- ================= SIDEBAR ================= -->
<?php include "sidebar.php"; ?>


    <!-- ================= MAIN CONTENT ================= -->

    <main class="dashboard-main">


        <!-- TOP HEADER -->

        <?php include "../includes/portal_header.php"; ?>


        <!-- ================= SCHEDULE CONTENT ================= -->

        <section class="dashboard-content schedule-page-content">


            <!-- PAGE INTRO -->

            <div class="section-heading schedule-heading">

                <div>

                    <h2>Vaccination Schedule</h2>

                    <p>
                        Keep track of your children's upcoming vaccinations
                    </p>

                </div>

            </div>


            <!-- ================= SCHEDULE LIST ================= -->

            <?php if (mysqli_num_rows($schedule_result) > 0): ?>

                <div class="schedule-list">

                    <?php while ($schedule = mysqli_fetch_assoc($schedule_result)): ?>

                        <?php
                        $status_class = strtolower($schedule["status"]);
                        ?>

                        <div class="schedule-card">


                            <!-- CARD HEADER -->

                            <div class="schedule-card-header">

                                <div class="schedule-child">

                                    <div class="schedule-child-icon">
                                        ♙
                                    </div>

                                    <div>

                                        <span class="schedule-label">
                                            CHILD
                                        </span>

                                        <h3>
                                            <?php echo htmlspecialchars($schedule["child_name"]); ?>
                                        </h3>

                                    </div>

                                </div>


                                <span class="schedule-status <?php echo $status_class; ?>">
                                    <?php echo htmlspecialchars($schedule["status"]); ?>
                                </span>

                            </div>


                            <!-- VACCINE -->

                            <div class="schedule-vaccine">

                                <span class="schedule-label">
                                    VACCINATION
                                </span>

                                <h3>
                                    <?php echo htmlspecialchars($schedule["vaccine_name"]); ?>
                                </h3>

                                <span class="schedule-dose">
                                    Dose <?php echo htmlspecialchars($schedule["dose_number"]); ?>
                                </span>

                            </div>


                            <!-- DETAILS -->

                            <div class="schedule-details">

                                <div class="schedule-detail">

                                    <div class="schedule-detail-icon">
                                        ▣
                                    </div>

                                    <div>

                                        <span>
                                            DATE
                                        </span>

                                        <strong>
                                            <?php
                                            echo date(
                                                "d M Y",
                                                strtotime($schedule["scheduled_date"])
                                            );
                                            ?>
                                        </strong>

                                    </div>

                                </div>


                                <div class="schedule-detail">

                                    <div class="schedule-detail-icon">
                                        ◷
                                    </div>

                                    <div>

                                        <span>
                                            TIME
                                        </span>

                                        <strong>
                                            <?php
                                            echo date(
                                                "h:i A",
                                                strtotime($schedule["scheduled_time"])
                                            );
                                            ?>
                                        </strong>

                                    </div>

                                </div>

                            </div>


                        </div>

                    <?php endwhile; ?>

                </div>


            <?php else: ?>


                <!-- EMPTY STATE -->

                <div class="schedule-empty-card">

                    <div class="schedule-empty-icon">
                        ▣
                    </div>

                    <h3>
                        No vaccination schedules yet
                    </h3>

                    <p>
                        Your children's upcoming vaccination schedules
                        will appear here once they are assigned.
                    </p>

                    <a href="book_appointment.php" class="dashboard-primary-btn">
                        Book an Appointment
                    </a>

                </div>


            <?php endif; ?>


        </section>

    </main>

</div>

</body>

</html>