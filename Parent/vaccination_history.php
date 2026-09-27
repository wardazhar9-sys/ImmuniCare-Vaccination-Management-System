<?php
require_once "../includes/app.php";
$user = require_role($conn, "parent");
$name = $user["name"];
$parent_id = (int)$user["id"];

$stmt = $conn->prepare(
    "SELECT vr.id, c.child_name, v.vaccine_name,
            COALESCE(d.dose_number, vr.dose_number) AS dose_number,
            h.hospital_name, h.city, vr.vaccination_date, vr.status, vr.remarks
     FROM vaccination_records vr
     JOIN children c ON c.id = vr.child_id
     JOIN vaccines v ON v.id = vr.vaccine_id
     LEFT JOIN vaccine_doses d ON d.id = vr.vaccine_dose_id
     JOIN hospitals h ON h.id = vr.hospital_id
     WHERE c.parent_id = ? ORDER BY vr.vaccination_date DESC"
);
$stmt->bind_param("i", $parent_id);
$stmt->execute();
$history_result = $stmt->get_result();

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Vaccination History | ImmuniCare</title>

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


        <!-- ================= HISTORY CONTENT ================= -->

        <section class="dashboard-content history-page-content">


            <!-- PAGE INTRO -->

            <div class="section-heading history-heading">

                <div>

                    <h2>Vaccination History</h2>

                    <p>
                        A record of vaccinations received by your children
                    </p>

                </div>

            </div>


            <!-- ================= HISTORY LIST ================= -->

            <?php if (mysqli_num_rows($history_result) > 0): ?>

                <div class="history-list">

                    <?php while ($record = mysqli_fetch_assoc($history_result)): ?>

                        <?php
                        $status_class = strtolower($record["status"]);
                        ?>

                        <div class="history-card">


                            <!-- CARD HEADER -->

                            <div class="history-card-header">

                                <div class="history-child">

                                    <div class="history-child-icon">
                                        ♙
                                    </div>

                                    <div>

                                        <span class="history-label">
                                            CHILD
                                        </span>

                                        <h3>
                                            <?php echo htmlspecialchars($record["child_name"]); ?>
                                        </h3>

                                    </div>

                                </div>


                                <span class="history-status <?php echo $status_class; ?>">
                                    <?php echo htmlspecialchars($record["status"]); ?>
                                </span>

                            </div>


                            <!-- VACCINE -->

                            <div class="history-vaccine">

                                <span class="history-label">
                                    VACCINATION
                                </span>

                                <h3>
                                    <?php echo htmlspecialchars($record["vaccine_name"]); ?>
                                </h3>

                                <span class="history-dose">
                                    Dose <?php echo htmlspecialchars($record["dose_number"]); ?>
                                </span>

                            </div>


                            <!-- DETAILS -->

                            <div class="history-details">

                                <div class="history-detail">

                                    <div class="history-detail-icon">
                                        ♙
                                    </div>

                                    <div>

                                        <span>
                                            HOSPITAL
                                        </span>

                                        <strong>
                                            <?php echo htmlspecialchars($record["hospital_name"]); ?>
                                        </strong>

                                        <small>
                                            <?php echo htmlspecialchars($record["city"]); ?>
                                        </small>

                                    </div>

                                </div>


                                <div class="history-detail">

                                    <div class="history-detail-icon">
                                        ▣
                                    </div>

                                    <div>

                                        <span>
                                            VACCINATION DATE
                                        </span>

                                        <strong>
                                            <?php
                                            echo date(
                                                "d M Y",
                                                strtotime($record["vaccination_date"])
                                            );
                                            ?>
                                        </strong>

                                    </div>

                                </div>

                            </div>


                            <!-- REMARKS -->

                            <?php if (!empty($record["remarks"])): ?>

                                <div class="history-remarks">

                                    <span class="history-label">
                                        REMARKS
                                    </span>

                                    <p>
                                        <?php echo htmlspecialchars($record["remarks"]); ?>
                                    </p>

                                </div>

                            <?php endif; ?>


                        </div>

                    <?php endwhile; ?>

                </div>


            <?php else: ?>


                <!-- EMPTY STATE -->

                <div class="history-empty-card">

                    <div class="history-empty-icon">
                        ✓
                    </div>

                    <h3>
                        No vaccination history yet
                    </h3>

                    <p>
                        Your children's completed vaccinations will appear
                        here once they are recorded by the hospital.
                    </p>

                    <a href="vaccines.php" class="dashboard-primary-btn">
                        View Vaccines
                    </a>

                </div>


            <?php endif; ?>


        </section>

    </main>

</div>

</body>

</html>