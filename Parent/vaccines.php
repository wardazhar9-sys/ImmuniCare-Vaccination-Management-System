<?php

session_start();

require_once "../config/db.php";

// Make sure only logged-in parents can access this page
if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit();
}

if ($_SESSION["role"] !== "parent") {
    header("Location: ../login.php");
    exit();
}

// Get parent's name
$name = $_SESSION["name"];

// Get all vaccines from the database
$sql = "SELECT * FROM vaccines ORDER BY id ASC";

$vaccines_result = mysqli_query($conn, $sql);

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

    <aside class="dashboard-sidebar">

        <div class="sidebar-brand">
            <img src="../assets/images/immunicare-logo-sidebar.svg"
                 alt="ImmuniCare Parent Portal"
                 class="sidebar-brand-image">
        </div>

        <nav class="sidebar-nav">

            <div class="nav-section-title">
                MAIN MENU
            </div>

            <a href="dashboard.php" class="sidebar-link">
                <span class="sidebar-icon">⌂</span>
                <span>Dashboard</span>
            </a>

            <a href="children.php" class="sidebar-link">
                <span class="sidebar-icon">♙</span>
                <span>My Children</span>
            </a>

            <a href="vaccines.php" class="sidebar-link active">
                <span class="sidebar-icon">✚</span>
                <span>Vaccines</span>
            </a>

            <a href="schedule.php" class="sidebar-link">
                <span class="sidebar-icon">▣</span>
                <span>Vaccination Schedule</span>
            </a>


            <div class="nav-section-title dashboard-nav-spacing">
                APPOINTMENTS
            </div>

            <a href="book_appointment.php" class="sidebar-link">
                <span class="sidebar-icon">＋</span>
                <span>Book Appointment</span>
            </a>

            <a href="bookings.php" class="sidebar-link">
                <span class="sidebar-icon">▤</span>
                <span>My Bookings</span>
            </a>


            <div class="nav-section-title dashboard-nav-spacing">
                HEALTH RECORDS
            </div>

            <a href="vaccination_history.php" class="sidebar-link">
                <span class="sidebar-icon">✓</span>
                <span>Vaccination History</span>
            </a>

            <a href="profile.php" class="sidebar-link">
                <span class="sidebar-icon">◯</span>
                <span>My Profile</span>
            </a>

        </nav>


        <div class="sidebar-bottom">

            <a href="logout.php" class="logout-link">
                <span class="sidebar-icon">↪</span>
                <span>Logout</span>
            </a>

        </div>

    </aside>


    <!-- ================= MAIN CONTENT ================= -->

    <main class="dashboard-main">


        <!-- ================= HEADER ================= -->

        <header class="dashboard-header">

            <div class="header-page-title">

                <h1>Vaccines</h1>

                <p>
                    Explore vaccines available through ImmuniCare
                </p>

            </div>


            <div class="header-actions">

                <button class="notification-button" type="button">

                    <span>♢</span>

                    <span class="notification-dot"></span>

                </button>


                <div class="header-divider"></div>


                <div class="profile-mini">

                    <div class="profile-avatar">
                        <?php echo strtoupper(substr($name, 0, 1)); ?>
                    </div>

                    <div class="profile-info">

                        <strong>
                            <?php echo htmlspecialchars($name); ?>
                        </strong>

                        <span>
                            Parent Account
                        </span>

                    </div>

                </div>

            </div>

        </header>


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


 <a href="book_appointment.php?vaccine_id=<?php echo $vaccine["id"]; ?>"
   class="dashboard-primary-btn vaccine-book-btn">

    Book Appointment

</a>


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