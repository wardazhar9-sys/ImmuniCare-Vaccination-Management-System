<?php

include("config/db.php");

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Hospitals | ImmuniCare</title>

    <link rel="stylesheet" href="assets/css/style.css">

</head>

<body>


<?php include("includes/navbar.php"); ?>


<!-- =========================================
     HOSPITALS HERO
========================================= -->

<section class="hospitals-hero">

    <div class="hospitals-hero-container">

        <div class="hospitals-hero-content">

            <div class="hospitals-label">
                TRUSTED HEALTHCARE
            </div>

            <h1>
                Find a Hospital
                <span>Near You</span>
            </h1>

            <p>
                Explore hospitals available through the ImmuniCare
                vaccination system and find the right place for
                your child's vaccination needs.
            </p>

        </div>

    </div>

</section>


<!-- =========================================
     HOSPITALS SECTION
========================================= -->

<section class="hospitals-section">

    <div class="hospitals-container">

        <div class="hospitals-heading">

            <div class="hospitals-small-label">
                AVAILABLE HOSPITALS
            </div>

            <h2>
                Connect With
                <span>Trusted Hospitals</span>
            </h2>

            <p>
                Find hospitals available through the ImmuniCare
                vaccination system.
            </p>

        </div>


        <!-- SEARCH -->

        <div class="hospitals-search">

            <input
                type="text"
                id="hospitalSearch"
                placeholder="Search for a hospital..."
            >

        </div>


        <!-- HOSPITAL LIST -->

        <div class="hospitals-list">

            <?php

            $query = "SELECT * FROM hospitals WHERE status = 'Active' ORDER BY id ASC";

            $result = mysqli_query($conn, $query);

            if ($result && mysqli_num_rows($result) > 0) {

                while ($hospital = mysqli_fetch_assoc($result)) {

            ?>

                <div
                    class="hospital-card"
                    data-name="<?php echo strtolower(htmlspecialchars($hospital['hospital_name'])); ?>"
                    data-location="<?php echo strtolower(htmlspecialchars($hospital['location'])); ?>"
                >

                    <div class="hospital-card-header">

                        <div class="hospital-icon">
                            🏥
                        </div>

                        <div class="hospital-number">
                            <?php echo str_pad($hospital['id'], 2, '0', STR_PAD_LEFT); ?>
                        </div>

                    </div>


                    <div class="hospital-card-content">

                        <h3>
                            <?php echo htmlspecialchars($hospital['hospital_name']); ?>
                        </h3>

                        <div class="hospital-info">

                            <div class="hospital-info-row">

                                <span class="hospital-info-icon">
                                    📍
                                </span>

                                <div>
                                    <small>Location</small>

                                    <strong>
                                        <?php echo htmlspecialchars($hospital['location']); ?>
                                    </strong>
                                </div>

                            </div>


                            <div class="hospital-info-row">

                                <span class="hospital-info-icon">
                                    🏙️
                                </span>

                                <div>
                                    <small>City</small>

                                    <strong>
                                        <?php echo htmlspecialchars($hospital['city']); ?>
                                    </strong>
                                </div>

                            </div>


                            <?php if (!empty($hospital['phone'])) { ?>

                                <div class="hospital-info-row">

                                    <span class="hospital-info-icon">
                                        📞
                                    </span>

                                    <div>
                                        <small>Contact</small>

                                        <strong>
                                            <?php echo htmlspecialchars($hospital['phone']); ?>
                                        </strong>
                                    </div>

                                </div>

                            <?php } ?>

                        </div>


                        <div class="hospital-card-footer">

                            <span class="hospital-status">
                                <?php echo htmlspecialchars($hospital['status']); ?>
                            </span>

                           

                        </div>

                    </div>

                </div>

            <?php

                }

            } else {

            ?>

                <div class="no-hospitals">

                    <h3>No Hospitals Available</h3>

                    <p>
                        There are currently no active hospitals available.
                    </p>

                </div>

            <?php

            }

            ?>

        </div>

    </div>

</section>


<script>

const hospitalSearch = document.getElementById("hospitalSearch");

const hospitalCards = document.querySelectorAll(".hospital-card");


hospitalSearch.addEventListener("keyup", function () {

    const searchValue = this.value.toLowerCase();


    hospitalCards.forEach(function (card) {

        const hospitalName = card.getAttribute("data-name");

        const hospitalLocation = card.getAttribute("data-location");


        if (
            hospitalName.includes(searchValue) ||
            hospitalLocation.includes(searchValue)
        ) {

            card.style.display = "";

        } else {

            card.style.display = "none";

        }

    });

});

</script>


<?php include("includes/footer.php"); ?>


</body>

</html>