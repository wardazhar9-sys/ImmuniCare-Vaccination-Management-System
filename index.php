<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ImmuniCare-Vaccination Management System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php
include("includes/navbar.php");
?>
<section class="hero">
    <div class="hero-container">
        <div class="hero-content">
            <div class="hero-badge">
                <span class="badge-dot"></span>
                Smart Vaccination Management
            </div>
            <h1>
                A Healthier
                <span>Tomorrow</span> Starts
                with Vaccination
            </h1>
            <div class="hero-line"></div>
            <p>
                Protect your family, strengthen your community, and build a
                healthier future with ImmuniCare. Easy booking, trusted
                hospitals, and complete vaccination support for your
                children.
            </p>
            <div class="hero-buttons">
                <a href="register.php" class="hero-primary-btn">
                    Get Started
                    <span>→</span>
                </a>
                <a href="#about" class="hero-secondary-btn">
                    Learn More
                </a>
            </div>
        </div>
        <div class="hero-visual">
            <img
                src="assets/images/immunicare-logo-header.svg"
                alt="ImmuniCare vaccination illustration"
                class="hero-doctor-image"
            >
        </div>
    </div>
</section>
<section class="home-features">
    <div class="features-bg-circle features-circle-left"></div>
    <div class="features-bg-circle features-circle-right"></div>
    <div class="features-dots features-dots-top"></div>
    <div class="features-dots features-dots-bottom"></div>
    <div class="home-features-container">
        <div class="home-section-heading">
            <div class="home-section-label">
                WHY IMMUNICARE
            </div>
            <h2>
                Everything You Need to Keep
                <span>Your Child Protected</span>
            </h2>
            <p>
                ImmuniCare makes childhood vaccination simple, organized,
                and easy to manage — all in one place.
            </p>
        </div>
        <div class="home-feature-grid">
            <div class="home-feature-card feature-blue">
                <div class="home-feature-icon">
                    <svg viewBox="0 0 64 64" aria-hidden="true">
                        <rect x="14" y="12" width="36" height="40"
                              rx="5"
                              fill="#ffffff"
                              stroke="#2563eb"
                              stroke-width="4"/>
                        <line x1="22" y1="8" x2="22" y2="17"
                              stroke="#2563eb"
                              stroke-width="4"
                              stroke-linecap="round"/>
                        <line x1="42" y1="8" x2="42" y2="17"
                              stroke="#2563eb"
                              stroke-width="4"
                              stroke-linecap="round"/>
                        <line x1="14" y1="23" x2="50" y2="23"
                              stroke="#2563eb"
                              stroke-width="4"/>
                        <circle cx="23" cy="32" r="2.5" fill="#38bdf8"/>
                        <circle cx="32" cy="32" r="2.5" fill="#38bdf8"/>
                        <circle cx="41" cy="32" r="2.5" fill="#38bdf8"/>
                        <circle cx="23" cy="41" r="2.5" fill="#38bdf8"/>
                        <circle cx="32" cy="41" r="2.5" fill="#38bdf8"/>
                        <circle cx="41" cy="41" r="2.5" fill="#38bdf8"/>
                    </svg>
                </div>
                <div class="home-feature-spark">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
                <h3>Vaccination Scheduling</h3>
                <p>
                    Keep track of upcoming vaccinations and important
                    vaccination dates for your child.
                </p>
                <div class="home-feature-arrow">
                    →
                </div>
            </div>
            <div class="home-feature-card feature-teal">
                <div class="home-feature-icon">
                    <svg viewBox="0 0 64 64" aria-hidden="true">
                        <path
                            d="M32 8
                               L49 15
                               V29
                               C49 41 42 50 32 55
                               C22 50 15 41 15 29
                               V15
                               Z"
                            fill="#14b8a6"
                            opacity="0.95"/>
                        <path
                            d="M32 19
                               V39
                               M22 29
                               H42"
                            stroke="#ffffff"
                            stroke-width="5"
                            stroke-linecap="round"/>
                    </svg>
                </div>
                <div class="home-feature-spark">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
                <h3>Secure Records</h3>
                <p>
                    Keep your child's vaccination information organized
                    and accessible in one secure place.
                </p>
                <div class="home-feature-arrow">
                    →
                </div>
            </div>
            <div class="home-feature-card feature-indigo">
                <div class="home-feature-icon">
                    <svg viewBox="0 0 64 64" aria-hidden="true">
                        <rect x="10" y="23" width="44" height="28"
                              rx="3"
                              fill="#2563eb"/>
                        <path
                            d="M7 23
                               L14 14
                               H50
                               L57 23
                               Z"
                            fill="#60a5fa"/>
                        <rect x="17" y="29" width="10" height="10"
                              rx="1"
                              fill="#ffffff"/>
                        <rect x="31" y="29" width="8" height="8"
                              rx="1"
                              fill="#ffffff"/>
                        <rect x="43" y="29" width="6" height="12"
                              rx="1"
                              fill="#dbeafe"/>
                        <rect x="17" y="42" width="10" height="9"
                              fill="#dbeafe"/>
                        <rect x="31" y="42" width="8" height="9"
                              fill="#dbeafe"/>
                        <circle cx="46" cy="17" r="7"
                                fill="#14b8a6"/>
                        <path
                            d="M46 13 V21 M42 17 H50"
                            stroke="#ffffff"
                            stroke-width="2.5"
                            stroke-linecap="round"/>
                    </svg>
                </div>
                <div class="home-feature-spark">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
                <h3>Trusted Hospitals</h3>
                <p>
                    Find and connect with hospitals available through
                    the ImmuniCare vaccination system.
                </p>
                <div class="home-feature-arrow">
                    →
                </div>
            </div>
            <div class="home-feature-card feature-orange">
                <div class="home-feature-icon">
                    <svg viewBox="0 0 64 64" aria-hidden="true">
                        <path
                            d="M20 28
                               C20 18 25 13 32 13
                               C39 13 44 18 44 28
                               V39
                               L49 45
                               H15
                               L20 39
                               Z"
                            fill="#fbbf24"/>
                        <path
                            d="M27 50
                               C28 55 36 55 37 50"
                            fill="none"
                            stroke="#f59e0b"
                            stroke-width="3"
                            stroke-linecap="round"/>
                        <circle cx="32" cy="9" r="2.5"
                                fill="#f59e0b"/>
                    </svg>
                </div>
                <div class="home-feature-spark">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
                <h3>Helpful Reminders</h3>
                <p>
                    Stay aware of upcoming vaccination appointments and
                    important vaccination schedules.
                </p>
                <div class="home-feature-arrow">
                    →
                </div>
            </div>
        </div>
    </div>
</section>
<section class="home-services">
    <div class="home-services-container">
        <div class="home-services-content">
            <div class="home-services-label">
                OUR SERVICES
            </div>
            <h2>
                Complete Vaccination
                <span>Support for Your Child</span>
            </h2>
            <p>
                ImmuniCare brings essential vaccination services together
                in one simple platform, helping parents stay organized
                and connected throughout their child's vaccination journey.
            </p>
            <a href="register.php" class="home-services-btn">
                Get Started
                <span>→</span>
            </a>
        </div>
        <div class="home-services-list">
            <div class="home-service-item">
                <div class="home-service-icon service-blue">
                    <span>📅</span>
                </div>
                <div class="home-service-text">
                    <h3>Easy Appointment Booking</h3>
                    <p>
                        Book vaccination appointments with available
                        hospitals quickly and easily.
                    </p>
                </div>
            </div>
            <div class="home-service-item">
                <div class="home-service-icon service-teal">
                    <span>💉</span>
                </div>
                <div class="home-service-text">
                    <h3>Vaccination Tracking</h3>
                    <p>
                        Keep track of your child's vaccination history
                        and upcoming vaccinations.
                    </p>
                </div>
            </div>
            <div class="home-service-item">
                <div class="home-service-icon service-indigo">
                    <span>🏥</span>
                </div>
                <div class="home-service-text">
                    <h3>Hospital Connections</h3>
                    <p>
                        Connect with hospitals available through the
                        ImmuniCare vaccination system.
                    </p>
                </div>
            </div>
            <div class="home-service-item">
                <div class="home-service-icon service-yellow">
                    <span>🔔</span>
                </div>
                <div class="home-service-text">
                    <h3>Vaccination Reminders</h3>
                    <p>
                        Stay informed about upcoming appointments and
                        important vaccination schedules.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>
<section class="home-how-it-works">
    <div class="home-how-container">
        <div class="home-how-heading">
            <div class="home-how-label">
                HOW IT WORKS
            </div>
            <h2>
                Simple Steps to Better
                <span>Vaccination Care</span>
            </h2>
            <p>
                ImmuniCare makes it easy to manage your child's
                vaccination journey from start to finish.
            </p>
        </div>
        <div class="home-how-steps">
            <div class="home-how-step">
                <div class="home-how-number">
                    01
                </div>
                <div class="home-how-icon">
                    <span>👶</span>
                </div>
                <h3>
                    Register Your Child
                </h3>
                <p>
                    Add your child's basic information and
                    create their vaccination profile.
                </p>
            </div>
            <div class="home-how-step">
                <div class="home-how-number">
                    02
                </div>
                <div class="home-how-icon">
                    <span>📅</span>
                </div>
                <h3>
                    Book a Vaccination
                </h3>
                <p>
                    Select the required vaccine and book
                    an appointment with an available hospital.
                </p>
            </div>
            <div class="home-how-step">
                <div class="home-how-number">
                    03
                </div>
                <div class="home-how-icon">
                    <span>🛡️</span>
                </div>
                <h3>
                    Track & Stay Protected
                </h3>
                <p>
                    Keep track of vaccination records,
                    upcoming doses, and appointments.
                </p>
            </div>
        </div>
    </div>
</section>
<section class="home-cta">
    <div class="home-cta-container">
        <div class="home-cta-content">
            <div class="home-cta-label">
                GET STARTED TODAY
            </div>
            <h2>
                Give Your Child a
                <span>Healthier Tomorrow</span>
            </h2>
            <p>
                Start managing your child's vaccination journey
                with ImmuniCare. Keep records organized, book
                appointments, and stay on track with ease.
            </p>
            <div class="home-cta-buttons">
                <a href="register.php" class="home-cta-primary">
                    Get Started
                    <span>→</span>
                </a>
                <a href="about.php" class="home-cta-secondary">
                    Learn More
                </a>
            </div>
        </div>
    </div>
</section>
<?php include("includes/footer.php"); ?>
</body>
</html>
