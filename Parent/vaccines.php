<?php
require_once "../includes/app.php";
$user = require_role($conn, "parent");
$name = $user["name"];

$vaccines_result = $conn->query(
    "SELECT id, vaccine_name, description, age_group, dose_number, availability
     FROM vaccines ORDER BY vaccine_name"
);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Vaccines | ImmuniCare</title>

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


        <!-- ================= VACCINES CONTENT ================= -->

        <section class="dashboard-content">


            <div class="section-heading">

                <div>

                    <h2>
                        Available Vaccines
                    </h2>

                    <p>
                        Explore vaccination options available through ImmuniCare.
                    </p>

                </div>

            </div>

<div class="vaccine-search-wrapper">

    <input
        type="text"
        id="vaccineSearch"
        class="vaccine-search-input"
        placeholder="Search vaccines..."
    >

</div>


            <?php if (mysqli_num_rows($vaccines_result) > 0): ?>


                <div class="vaccines-grid">


                    <?php while ($vaccine = mysqli_fetch_assoc($vaccines_result)): ?>


                        <?php

                        $availability_class =
                            strtolower($vaccine["availability"]) === "available"
                            ? "available"
                            : "unavailable";

                        ?>


                        <div class="vaccine-card">


                            <div class="vaccine-card-icon">
                                ✚
                            </div>


                            <div class="vaccine-card-body">


                                <h3 class="vaccine-card-title">

                                    <?php echo htmlspecialchars($vaccine["vaccine_name"]); ?>

                                </h3>


                                <p class="vaccine-card-description">

                                    <?php echo htmlspecialchars($vaccine["description"]); ?>

                                </p>


                                <div class="vaccine-meta">


                                    <div class="vaccine-meta-item">

                                        <span class="vaccine-meta-label">
                                            Age Group
                                        </span>

                                        <span class="vaccine-meta-value">

                                            <?php echo htmlspecialchars($vaccine["age_group"]); ?>

                                        </span>

                                    </div>


                                    <div class="vaccine-meta-item">

                                        <span class="vaccine-meta-label">
                                            Dose
                                        </span>

                                        <span class="vaccine-meta-value">

                                            <?php echo htmlspecialchars($vaccine["dose_number"]); ?>

                                        </span>

                                    </div>


                                </div>


                            </div>


                            <div class="vaccine-card-footer">


                                <span class="vaccine-availability <?php echo $availability_class; ?>">

                                    <?php echo htmlspecialchars($vaccine["availability"]); ?>

                                </span>


 <?php if ($availability_class === "available"): ?>
     <a href="book_appointment.php?vaccine_id=<?php echo (int)$vaccine["id"]; ?>"
        class="dashboard-primary-btn vaccine-book-btn">Book Appointment</a>
 <?php else: ?>
     <span class="booking-no-action">Currently unavailable</span>
 <?php endif; ?>


                            </div>


                        </div>


                    <?php endwhile; ?>


                </div>


            <?php else: ?>


                <div class="dashboard-card">

                    <h3>
                        No vaccines available
                    </h3>

                    <p>
                        There are currently no vaccines available.
                    </p>

                </div>


            <?php endif; ?>


        </section>


    </main>

</div>

<!-- VACCINES SEARCH BAR FUNCTIONALITY -->
<script>
    const searchInput = document.getElementById("vaccineSearch");
    const vaccineCards = document.querySelectorAll(".vaccine-card");

    searchInput.addEventListener("input", function () {

        const searchText = searchInput.value.toLowerCase().trim();

        let visibleCards = 0;

        vaccineCards.forEach(function (card) {

            const vaccineName = card
                .querySelector(".vaccine-card-title")
                .textContent
                .toLowerCase();

            const vaccineDescription = card
                .querySelector(".vaccine-card-description")
                .textContent
                .toLowerCase();

            if (
                vaccineName.includes(searchText) ||
                vaccineDescription.includes(searchText)
            ) {

                card.style.display = "";

                visibleCards++;

            } else {

                card.style.display = "none";

            }

        });


        const existingMessage = document.getElementById("noVaccineMessage");

        if (existingMessage) {
            existingMessage.remove();
        }


        if (visibleCards === 0) {

            const message = document.createElement("div");

            message.id = "noVaccineMessage";
            message.className = "no-vaccine-message";

            message.innerHTML = `
                <div class="no-vaccine-icon">🔍</div>
                <h3>No vaccines found</h3>
                <p>We couldn't find a vaccine matching your search.</p>
            `;

            document
                .querySelector(".vaccines-grid")
                .appendChild(message);
        }

    });
</script>

</body>

</html>