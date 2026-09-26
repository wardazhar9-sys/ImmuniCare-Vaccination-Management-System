<?php
require_once "includes/app.php";
$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verify_csrf();
    $name = post_string("name", 100);
    $email = post_string("email", 150);
    $subject = post_string("subject", 200);
    $body = post_string("message", 2000);

    if ($name === "" || !filter_var($email, FILTER_VALIDATE_EMAIL) || $subject === "" || $body === "") {
        $message = "Please complete all contact fields correctly.";
        $message_type = "error";
    } else {
        $stmt = $conn->prepare(
            "INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)"
        );
        if ($stmt) {
            $stmt->bind_param("ssss", $name, $email, $subject, $body);
            $saved = $stmt->execute();
            $stmt->close();
        } else {
            error_log("Contact message table is unavailable: " . mysqli_error($conn));
            $saved = false;
        }
        $message = $saved ? "Your message has been received." : "Unable to send your message.";
        $message_type = $saved ? "success" : "error";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Contact ImmuniCare</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include "includes/navbar.php"; ?>
<main>

    <!-- =========================================
         CONTACT HERO
    ========================================== -->

    <section class="contact-hero">

        <div class="contact-hero-container">

            <span class="contact-label">
                GET IN TOUCH
            </span>

            <h1>
                Let's Stay Connected
            </h1>

            <p>
                Have a question about ImmuniCare?
                We're here to help you make your child's
                vaccination journey easier.
            </p>

        </div>

    </section>


    <!-- =========================================
         CONTACT SECTION
    ========================================== -->

    <section class="contact-main">

        <div class="contact-main-container">

            <!-- LEFT INFORMATION -->

            <div class="contact-info">

                <span class="contact-small-label">
                    CONTACT US
                </span>

                <h2>
                    We're here when you need us.
                </h2>

                <p class="contact-info-intro">
                    Whether you have a question about vaccines,
                    appointments, your account, or the ImmuniCare
                    platform, feel free to reach out.
                </p>


                <div class="contact-details">

                    <div class="contact-detail">

                        <div class="contact-detail-icon">
                            @
                        </div>

                        <div>
                            <span>Email</span>
                            <strong>support@immunicare.com</strong>
                        </div>

                    </div>


                    <div class="contact-detail">

                        <div class="contact-detail-icon">
                            +
                        </div>

                        <div>
                            <span>Phone</span>
                            <strong>+92 21 111 000 000</strong>
                        </div>

                    </div>


                    <div class="contact-detail">

                        <div class="contact-detail-icon">
                            ●
                        </div>

                        <div>
                            <span>Location</span>
                            <strong>Karachi, Pakistan</strong>
                        </div>

                    </div>


                    <div class="contact-detail">

                        <div class="contact-detail-icon">
                            ✓
                        </div>

                        <div>
                            <span>Support Hours</span>
                            <strong>Monday – Friday, 9 AM – 5 PM</strong>
                        </div>

                    </div>

                </div>


                <div class="contact-help">

                    <span>
                        NEED HELP?
                    </span>

                    <p>
                        Our team is ready to assist you
                        with your questions.
                    </p>

                </div>

            </div>


            <!-- RIGHT FORM -->

            <div class="contact-form-wrapper">

                <div class="contact-form-heading">

                    <span>
                        SEND A MESSAGE
                    </span>

                    <h2>
                        How can we help?
                    </h2>

                </div>


                <form
                    action=""
                    method="POST"
                    class="contact-form"
                >
                    <?php echo csrf_field(); ?>
                    <?php if ($message !== ""): ?><div class="appointment-message <?php echo e($message_type); ?>"><?php echo e($message); ?></div><?php endif; ?>

                    <div class="contact-form-row">

                        <div class="contact-field">

                            <label for="name">
                                Full Name
                            </label>

                            <input
                                type="text"
                                id="name"
                                name="name"
                                placeholder="Enter your name"
                                required
                            >

                        </div>


                        <div class="contact-field">

                            <label for="email">
                                Email Address
                            </label>

                            <input
                                type="email"
                                id="email"
                                name="email"
                                placeholder="Enter your email"
                                required
                            >

                        </div>

                    </div>


                    <div class="contact-field">

                        <label for="subject">
                            Subject
                        </label>

                        <input
                            type="text"
                            id="subject"
                            name="subject"
                            placeholder="What would you like to ask?"
                            required
                        >

                    </div>


                    <div class="contact-field">

                        <label for="message">
                            Message
                        </label>

                        <textarea
                            id="message"
                            name="message"
                            rows="6"
                            placeholder="Write your message here..."
                            required
                        ></textarea>

                    </div>


                    <button
                        type="submit"
                        class="contact-submit"
                    >
                        Send Message
                        <span>→</span>
                    </button>

                </form>

            </div>

        </div>

    </section>


    <!-- =========================================
         HOW CAN WE HELP
    ========================================== -->

    <section class="contact-help-section">

        <div class="contact-help-container">

            <div class="contact-help-heading">

                <span class="contact-small-label">
                    HOW CAN WE HELP?
                </span>

                <h2>
                    Choose what you need help with.
                </h2>

            </div>


            <div class="contact-help-grid">

                <div class="contact-help-item">

                    <span>01</span>

                    <h3>
                        Vaccination Information
                    </h3>

                    <p>
                        Questions about vaccines,
                        doses, or vaccination information.
                    </p>

                </div>


                <div class="contact-help-item">

                    <span>02</span>

                    <h3>
                        Appointments
                    </h3>

                    <p>
                        Need help understanding or
                        managing your vaccination appointment?
                    </p>

                </div>


                <div class="contact-help-item">

                    <span>03</span>

                    <h3>
                        Account Support
                    </h3>

                    <p>
                        Having trouble with your account
                        or accessing the platform?
                    </p>

                </div>


                <div class="contact-help-item">

                    <span>04</span>

                    <h3>
                        Technical Support
                    </h3>

                    <p>
                        Experiencing an issue while
                        using ImmuniCare?
                    </p>

                </div>

            </div>

        </div>

    </section>


    <!-- =========================================
         FINAL CTA
    ========================================== -->

    <section class="contact-cta">

        <div class="contact-cta-container">

            <span class="contact-small-label">
                GET STARTED
            </span>

            <h2>
                Ready to take the next step?
            </h2>

            <p>
                Create your ImmuniCare account and
                start organizing your child's vaccination journey.
            </p>

            <a
                href="register.php"
                class="contact-cta-button"
            >
                Create an Account
            </a>

        </div>

    </section>

</main>


<?php include("includes/footer.php"); ?>
</body>
</html>