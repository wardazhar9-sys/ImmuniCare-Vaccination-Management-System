<?php

$current_page = basename($_SERVER['PHP_SELF']);

?>

<nav class="navbar">

    <div class="navbar-container">

        <!-- IMMUNICARE LOGO -->

        <a href="index.php" class="brand">

            <img
                src="assets/images/immunicare-logo-header.svg"
                alt="ImmuniCare"
                class="brand-image"
            >

        </a>


        <!-- NAVIGATION LINKS -->

        <div class="nav-links">

            <a
                href="index.php"
                class="<?php echo ($current_page == 'index.php') ? 'active' : ''; ?>"
            >
                Home
            </a>

            <a
                href="vaccines.php"
                class="<?php echo ($current_page == 'vaccines.php') ? 'active' : ''; ?>"
            >
                Vaccines
            </a>

            <a
                href="hospitals.php"
                class="<?php echo ($current_page == 'hospitals.php') ? 'active' : ''; ?>"
            >
                Hospitals
            </a>

            <a
                href="about.php"
                class="<?php echo ($current_page == 'about.php') ? 'active' : ''; ?>"
            >
                About Us
            </a>

            <a
                href="contact.php"
                class="<?php echo ($current_page == 'contact.php') ? 'active' : ''; ?>"
            >
                Contact
            </a>

        </div>


        <!-- LOGIN / REGISTER BUTTONS -->

        <div class="nav-buttons">

            <a href="login.php" class="login-btn">
                Login
            </a>

            <a href="register.php" class="register-btn">
                Register
            </a>

        </div>

    </div>

</nav>