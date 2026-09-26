<?php

include("config/db.php");

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Vaccines | ImmuniCare</title>

    <link rel="stylesheet" href="assets/css/style.css">

</head>

<body>


<?php include("includes/navbar.php"); ?>


<!-- =========================================
     VACCINES HERO
========================================= -->

<section class="vaccines-hero">

    <div class="vaccines-hero-container">

        <div class="vaccines-hero-content">

            <div class="vaccines-label">
                VACCINATION GUIDE
            </div>

            <h1>
                Protecting Your Child
                <span>At Every Stage</span>
            </h1>

            <p>
                Explore important childhood vaccines, recommended
                age groups, and dose information to help you stay
                informed throughout your child's vaccination journey.
            </p>

        </div>

    </div>

</section>


<!-- =========================================
     VACCINE INFORMATION
========================================= -->

<section class="vaccines-section">

    <div class="vaccines-container">

        <div class="vaccines-heading">

            <div class="vaccines-small-label">
                AVAILABLE VACCINES
            </div>

            <h2>
                Explore Our
                <span>Vaccination Guide</span>
            </h2>

            <p>
                Find information about vaccines available through
                the ImmuniCare vaccination system.
            </p>

        </div>


        <!-- SEARCH -->

        <div class="vaccines-search">

            <input
                type="text"
                id="vaccineSearch"
                placeholder="Search for a vaccine..."
            >

        </div>


        <!-- VACCINE LIST -->

        <div class="vaccines-list">

            <?php

            $query = "SELECT * FROM vaccines ORDER BY id ASC";

            $result = mysqli_query($conn, $query);

            if ($result && mysqli_num_rows($result) > 0) {

                while ($vaccine = mysqli_fetch_assoc($result)) {

            ?>

                <div
                    class="vaccine-card"
                    data-name="<?php echo strtolower(htmlspecialchars($vaccine['vaccine_name'])); ?>"
                >

                    <div class="vaccine-card-top">

                        <div class="vaccine-icon">
                            💉
                        </div>

                        <div class="vaccine-status">
                            <?php echo htmlspecialchars($vaccine['availability']); ?>
                        </div>

                    </div>


                    <h3>
                        <?php echo htmlspecialchars($vaccine['vaccine_name']); ?>
                    </h3>


                    <p class="vaccine-description">
                        <?php echo htmlspecialchars($vaccine['description']); ?>
                    </p>


                    <div class="vaccine-details">

                        <div class="vaccine-detail">

                            <span>Recommended Age</span>

                            <strong>
                                <?php echo htmlspecialchars($vaccine['age_group']); ?>
                            </strong>

                        </div>


                        <div class="vaccine-detail">

                            <span>Dose</span>

                            <strong>
                                <?php echo htmlspecialchars($vaccine['dose_number']); ?>
                            </strong>

                        </div>

                    </div>

                </div>

            <?php

                }

            } else {

            ?>

                <div class="no-vaccines">

                    <h3>No Vaccines Available</h3>

                    <p>
                        There are currently no vaccines available.
                    </p>

                </div>

            <?php

            }

            ?>

        </div>

    </div>

</section>


<script>

const searchInput = document.getElementById("vaccineSearch");

const vaccineCards = document.querySelectorAll(".vaccine-card");


searchInput.addEventListener("keyup", function () {

    const searchValue = this.value.toLowerCase();


    vaccineCards.forEach(function (card) {

        const vaccineName = card.getAttribute("data-name");


        if (vaccineName.includes(searchValue)) {

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