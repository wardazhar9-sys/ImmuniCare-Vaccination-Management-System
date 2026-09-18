<?php
// ImmuniCare - Public Home Page
// This page is independent from the Parent/Hospital portal functionality.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ImmuniCare | Vaccinate Today, Healthier Tomorrows</title>

    <!-- Google Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Font Awesome for small UI icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <link rel="stylesheet" href="assets/css/home.css">
</head>

<body>

    <!-- ================= NAVBAR ================= -->
    <header class="site-header">
        <nav class="navbar container">

            <a href="index.php" class="brand">
                <span class="brand-mark">
                    <i class="fa-solid fa-shield-heart"></i>
                </span>

                <span class="brand-text">
                    <strong>ImmuniCare</strong>
                    <small>Vaccinate Today<br>Healthier Tomorrows</small>
                </span>
            </a>

            <button class="menu-toggle" id="menuToggle" aria-label="Open menu">
                <i class="fa-solid fa-bars"></i>
            </button>

            <div class="nav-area" id="navArea">
                <ul class="nav-links">
                    <li><a href="#home" class="active">Home</a></li>
                    <li><a href="#services">Services</a></li>
                    <li><a href="#about">About Us</a></li>
                    <li><a href="#contact">Contact</a></li>
                    <li><a href="#blog">Blog</a></li>
                </ul>

                <div class="nav-search">
                    <input type="text" placeholder="Search..." aria-label="Search">
                    <button type="button" aria-label="Search">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </button>
                </div>

                <div class="nav-actions">
                    <a href="login.php" class="btn btn-login">Login</a>
                    <a href="register.php" class="btn btn-register">Register</a>
                </div>
            </div>
        </nav>
    </header>


    <!-- ================= HERO ================= -->
    <main id="home">

        <section class="hero-section">
            <div class="hero-decoration hero-decoration-left"></div>
            <div class="hero-decoration hero-decoration-right"></div>

            <button class="hero-arrow hero-arrow-left" aria-label="Previous slide">
                <i class="fa-solid fa-chevron-left"></i>
            </button>

            <div class="container hero-grid">

                <div class="hero-content reveal">
                    <p class="eyebrow">SAFE VACCINES. BRIGHTER TOMORROWS.</p>

                    <h1>
                        A Healthier
                        <span>Tomorrow</span> Starts
                        with Vaccination
                    </h1>

                    <div class="heading-line"></div>

                    <p class="hero-description">
                        Protect your family, strengthen your community, and
                        build a healthier future with ImmuniCare. Easy booking,
                        trusted hospitals, and complete vaccination support
                        for your children.
                    </p>

                    <div class="hero-buttons">
                        <a href="register.php" class="btn btn-primary">
                            Get Started
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>

                        <a href="#about" class="btn btn-secondary">
                            Learn More
                        </a>
                    </div>

                    <div class="hero-dots" aria-label="Hero slides">
                        <button class="dot active"></button>
                        <button class="dot"></button>
                        <button class="dot"></button>
                        <button class="dot"></button>
                    </div>
                </div>

                <div class="hero-visual reveal">
                    <div class="visual-glow"></div>
                    <img src="assets/images/immunicare-hero-visual.png"
                         alt="Doctor and vaccination illustration">
                </div>

            </div>

            <button class="hero-arrow hero-arrow-right" aria-label="Next slide">
                <i class="fa-solid fa-chevron-right"></i>
            </button>
        </section>


        <!-- ================= FEATURE CARDS ================= -->
        <section class="features-section">
            <div class="container feature-grid">

                <article class="feature-card reveal">
                    <div class="feature-icon blue">
                        <i class="fa-regular fa-calendar-days"></i>
                    </div>
                    <div>
                        <h3>Easy Booking</h3>
                        <p>Schedule appointments<br>in just a few clicks</p>
                    </div>
                </article>

                <article class="feature-card reveal">
                    <div class="feature-icon teal">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>
                    <div>
                        <h3>Trusted Hospitals</h3>
                        <p>Partnered with certified<br>healthcare centers</p>
                    </div>
                </article>

                <article class="feature-card reveal">
                    <div class="feature-icon purple">
                        <i class="fa-solid fa-users"></i>
                    </div>
                    <div>
                        <h3>Complete Records</h3>
                        <p>Keep track of your<br>child's vaccinations</p>
                    </div>
                </article>

                <article class="feature-card reveal">
                    <div class="feature-icon pink">
                        <i class="fa-solid fa-heart"></i>
                    </div>
                    <div>
                        <h3>Healthier Communities</h3>
                        <p>Together we build<br>a safer tomorrow</p>
                    </div>
                </article>

            </div>
        </section>


        <!-- ================= SERVICES ================= -->
        <section class="services-section section-space" id="services">
            <div class="container">

                <div class="section-heading reveal">
                    <span class="section-label">OUR SERVICES</span>
                    <h2>Care for Every Stage<br>of Their Journey</h2>
                    <p>
                        ImmuniCare makes childhood vaccination easier to
                        understand, schedule, manage and track.
                    </p>
                </div>

                <div class="service-grid">

                    <article class="service-card reveal">
                        <div class="service-icon">
                            <i class="fa-solid fa-syringe"></i>
                        </div>
                        <h3>Vaccination Management</h3>
                        <p>
                            Explore vaccines and keep your child's
                            vaccination journey organized.
                        </p>
                        <a href="register.php">Explore <i class="fa-solid fa-arrow-right"></i></a>
                    </article>

                    <article class="service-card reveal">
                        <div class="service-icon">
                            <i class="fa-regular fa-calendar-check"></i>
                        </div>
                        <h3>Appointment Booking</h3>
                        <p>
                            Choose your child, vaccine, hospital and
                            convenient appointment time.
                        </p>
                        <a href="register.php">Get Started <i class="fa-solid fa-arrow-right"></i></a>
                    </article>

                    <article class="service-card reveal">
                        <div class="service-icon">
                            <i class="fa-solid fa-hospital"></i>
                        </div>
                        <h3>Trusted Hospitals</h3>
                        <p>
                            Connect vaccination appointments with
                            participating healthcare centers.
                        </p>
                        <a href="register.php">Find Care <i class="fa-solid fa-arrow-right"></i></a>
                    </article>

                    <article class="service-card reveal">
                        <div class="service-icon">
                            <i class="fa-solid fa-file-medical"></i>
                        </div>
                        <h3>Digital Vaccination Records</h3>
                        <p>
                            Keep vaccination schedules and completed
                            vaccination records together.
                        </p>
                        <a href="login.php">View Portal <i class="fa-solid fa-arrow-right"></i></a>
                    </article>

                </div>
            </div>
        </section>


        <!-- ================= ABOUT ================= -->
        <section class="about-section section-space" id="about">
            <div class="container about-grid">

                <div class="about-visual reveal">
                    <div class="about-circle"></div>

                    <div class="about-card card-one">
                        <i class="fa-solid fa-shield-virus"></i>
                        <strong>Protection</strong>
                        <span>Starts with prevention</span>
                    </div>

                    <div class="about-card card-two">
                        <i class="fa-solid fa-syringe"></i>
                        <strong>Vaccinate</strong>
                        <span>Protect every stage</span>
                    </div>

                    <div class="about-center-icon">
                        <i class="fa-solid fa-heart-pulse"></i>
                    </div>
                </div>

                <div class="about-content reveal">
                    <span class="section-label">ABOUT IMMUNICARE</span>

                    <h2>
                        Making Childhood
                        Vaccination Simpler
                    </h2>

                    <p>
                        ImmuniCare is designed to bring parents and
                        healthcare providers together through one simple
                        vaccination management system.
                    </p>

                    <div class="about-points">
                        <div>
                            <i class="fa-solid fa-circle-check"></i>
                            <span>Simple parent registration and child profiles</span>
                        </div>
                        <div>
                            <i class="fa-solid fa-circle-check"></i>
                            <span>Easy vaccination appointment management</span>
                        </div>
                        <div>
                            <i class="fa-solid fa-circle-check"></i>
                            <span>Schedules and vaccination history in one place</span>
                        </div>
                    </div>

                    <a href="register.php" class="btn btn-primary">
                        Join ImmuniCare
                        <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>

            </div>
        </section>


        <!-- ================= HOW IT WORKS ================= -->
        <section class="steps-section section-space">
            <div class="container">

                <div class="section-heading centered reveal">
                    <span class="section-label">HOW IT WORKS</span>
                    <h2>Vaccination Made Simple</h2>
                    <p>Follow a few simple steps to manage your child's vaccination journey.</p>
                </div>

                <div class="steps-grid">

                    <div class="step reveal">
                        <div class="step-number">01</div>
                        <div class="step-icon"><i class="fa-solid fa-user-plus"></i></div>
                        <h3>Create Account</h3>
                        <p>Register your ImmuniCare account to get started.</p>
                    </div>

                    <div class="step reveal">
                        <div class="step-number">02</div>
                        <div class="step-icon"><i class="fa-solid fa-child"></i></div>
                        <h3>Add Your Child</h3>
                        <p>Create a profile with your child's basic information.</p>
                    </div>

                    <div class="step reveal">
                        <div class="step-number">03</div>
                        <div class="step-icon"><i class="fa-solid fa-calendar-plus"></i></div>
                        <h3>Book Appointment</h3>
                        <p>Select the vaccine, hospital, date and time.</p>
                    </div>

                    <div class="step reveal">
                        <div class="step-number">04</div>
                        <div class="step-icon"><i class="fa-solid fa-shield-heart"></i></div>
                        <h3>Stay Protected</h3>
                        <p>Follow schedules and keep vaccination records updated.</p>
                    </div>

                </div>
            </div>
        </section>


        <!-- ================= VACCINATION SECTION ================= -->
        <section class="vaccination-section section-space">
            <div class="container vaccination-box">

                <div class="vaccination-content reveal">
                    <span class="section-label">VACCINATION CARE</span>

                    <h2>
                        Vaccinate.<br>
                        Protect.<br>
                        <span>Grow.</span>
                    </h2>

                    <p>
                        Keep your child's vaccination journey organized
                        from the first appointment to their completed
                        vaccination record.
                    </p>

                    <a href="register.php" class="btn btn-primary">
                        Start Your Journey
                        <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>

                <div class="vaccination-illustration reveal">
                    <div class="floating-syringe">
                        <i class="fa-solid fa-syringe"></i>
                    </div>

                    <div class="illustration-circle large"></div>
                    <div class="illustration-circle small"></div>

                    <div class="child-placeholder">
                        <i class="fa-solid fa-child-reaching"></i>
                    </div>

                    <div class="health-note">
                        <i class="fa-solid fa-syringe"></i>
                        <span>Vaccinate<br>Protect<br>Grow</span>
                        <i class="fa-solid fa-heart"></i>
                    </div>
                </div>

            </div>
        </section>


        <!-- ================= CTA ================= -->
        <section class="cta-section">
            <div class="cta-decoration"></div>

            <div class="container cta-content reveal">
                <span class="section-label light">START TODAY</span>

                <h2>
                    A Healthier Tomorrow<br>
                    Starts with You
                </h2>

                <p>
                    Take the next step toward keeping your child's
                    vaccination journey organized.
                </p>

                <div class="cta-buttons">
                    <a href="register.php" class="btn btn-white">
                        Create Free Account
                        <i class="fa-solid fa-arrow-right"></i>
                    </a>

                    <a href="login.php" class="btn btn-outline-white">
                        Login
                    </a>
                </div>
            </div>
        </section>

    </main>


    <!-- ================= FOOTER ================= -->
    <footer class="footer" id="contact">
        <div class="container footer-grid">

            <div class="footer-brand">
                <a href="index.php" class="brand footer-logo">
                    <span class="brand-mark">
                        <i class="fa-solid fa-shield-heart"></i>
                    </span>

                    <span class="brand-text">
                        <strong>ImmuniCare</strong>
                        <small>Vaccinate Today<br>Healthier Tomorrows</small>
                    </span>
                </a>

                <p>
                    Making childhood vaccination easier,
                    organized and accessible.
                </p>
            </div>

            <div class="footer-column">
                <h4>Quick Links</h4>
                <a href="#home">Home</a>
                <a href="#services">Services</a>
                <a href="#about">About Us</a>
                <a href="#contact">Contact</a>
            </div>

            <div class="footer-column" id="blog">
                <h4>For Parents</h4>
                <a href="register.php">Register</a>
                <a href="login.php">Login</a>
                <a href="register.php">Book Appointment</a>
                <a href="register.php">Track Vaccinations</a>
            </div>

            <div class="footer-column">
                <h4>Contact</h4>
                <p><i class="fa-solid fa-location-dot"></i> Karachi, Pakistan</p>
                <p><i class="fa-solid fa-envelope"></i> support@immunicare.com</p>
                <p><i class="fa-solid fa-phone"></i> +92 300 0000000</p>
            </div>

        </div>

        <div class="container footer-bottom">
            <p>© <?php echo date("Y"); ?> ImmuniCare. All rights reserved.</p>

            <div class="social-links">
                <a href="#" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
                <a href="#" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
                <a href="#" aria-label="LinkedIn"><i class="fa-brands fa-linkedin-in"></i></a>
            </div>
        </div>
    </footer>

    <script src="assets/js/home.js"></script>
</body>
</html>
