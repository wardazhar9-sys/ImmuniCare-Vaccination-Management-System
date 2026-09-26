<?php

require_once "includes/app.php";

$message = "";
$message_type = "";

$name = "";
$email = "";
$password = "";
$confirm_password = "";
$role = "";
$phone = "";
$address = "";
$city = "";
$location = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    verify_csrf();

    // Get form data
    $name = post_string("name", 100);
    $email = post_string("email", 150);
    $password = (string)($_POST["password"] ?? "");
    $confirm_password = (string)($_POST["confirm_password"] ?? "");
    $role = post_string("role", 20);
    $phone = post_string("phone", 30);
    $address = post_string("address", 500);
    $city = post_string("city", 100);
    $location = post_string("location", 255);


    // Check if all fields are filled
    if (
        empty($name) ||
        empty($email) ||
        empty($password) ||
        empty($confirm_password) ||
        empty($role) ||
        ($role === "hospital" &&
            (empty($phone) || empty($address) || empty($city)))
    ) {

        $message = "Please fill in all fields.";
        $message_type = "error";

    }


    // Check email format
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "Please enter a valid email address.";
        $message_type = "error";

    }


    // Check password length
    elseif (strlen($password) < 8) {

        $message = "Password must be at least 8 characters long.";
        $message_type = "error";

    }


    // Check whether passwords match
    elseif ($password !== $confirm_password) {

        $message = "Passwords do not match.";
        $message_type = "error";

    }


    // Make sure only allowed public roles are accepted
    elseif ($role !== "parent" && $role !== "hospital") {

        $message = "Invalid account type.";
        $message_type = "error";

    }


    else {

        // Check whether email already exists
        $check_sql = "SELECT id FROM users WHERE email = ?";

        $check_stmt = mysqli_prepare($conn, $check_sql);

        mysqli_stmt_bind_param(
            $check_stmt,
            "s",
            $email
        );

        mysqli_stmt_execute($check_stmt);

        mysqli_stmt_store_result($check_stmt);


        if (mysqli_stmt_num_rows($check_stmt) > 0) {

            $message = "An account with this email already exists.";
            $message_type = "error";

        }

        else {

            // Hash the password
            $hashed_password = password_hash(
                $password,
                PASSWORD_DEFAULT
            );


            // Insert user into users table
            $insert_sql = "INSERT INTO users
                           (name, email, password, role)
                           VALUES (?, ?, ?, ?)";

            $insert_stmt = mysqli_prepare(
                $conn,
                $insert_sql
            );

            mysqli_stmt_bind_param(
                $insert_stmt,
                "ssss",
                $name,
                $email,
                $hashed_password,
                $role
            );


            mysqli_begin_transaction($conn);

            if (mysqli_stmt_execute($insert_stmt)) {
                $user_id = mysqli_insert_id($conn);
                $hospital_ok = true;

                if ($role === "hospital") {
                    $hospital_stmt = mysqli_prepare(
                        $conn,
                        "INSERT INTO hospitals
                         (user_id, hospital_name, phone, address, city, location, status)
                         VALUES (?, ?, ?, ?, ?, ?, 'Pending')"
                    );
                    mysqli_stmt_bind_param(
                        $hospital_stmt,
                        "isssss",
                        $user_id,
                        $name,
                        $phone,
                        $address,
                        $city,
                        $location
                    );
                    $hospital_ok = mysqli_stmt_execute($hospital_stmt);
                    mysqli_stmt_close($hospital_stmt);
                }

                if ($hospital_ok) {
                    $plain_token = bin2hex(random_bytes(32));
                    $token_hash = hash("sha256", $plain_token);
                    $token_stmt = mysqli_prepare(
                        $conn,
                        "INSERT INTO email_verification_tokens
                         (user_id, token_hash, expires_at)
                         VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 24 HOUR))"
                    );
                    if ($token_stmt) {
                        mysqli_stmt_bind_param($token_stmt, "is", $user_id, $token_hash);
                        $token_ok = mysqli_stmt_execute($token_stmt);
                        mysqli_stmt_close($token_stmt);
                    } else {
                        error_log("Email verification table is unavailable: " . mysqli_error($conn));
                        $token_ok = true;
                    }

                    if ($token_ok) {
                        $payload = json_encode([
                            "email" => $email,
                            "url" => "verify_email.php?token=" . $plain_token
                        ]);
                        $channel = "email";
                        $event = "email_verification";
                        $outbox_stmt = mysqli_prepare(
                            $conn,
                            "INSERT INTO notification_outbox
                             (user_id, channel, event_type, payload)
                             VALUES (?, ?, ?, ?)"
                        );
                        if ($outbox_stmt) {
                            mysqli_stmt_bind_param(
                                $outbox_stmt,
                                "isss",
                                $user_id,
                                $channel,
                                $event,
                                $payload
                            );
                            $outbox_stmt->execute();
                            mysqli_stmt_close($outbox_stmt);
                        } else {
                            error_log("Notification outbox is unavailable: " . mysqli_error($conn));
                        }
                    }
                }

                if (!$hospital_ok) {
                    mysqli_rollback($conn);
                } else {
                    mysqli_commit($conn);
                }

                if ($hospital_ok) {
                    $message = "Account created successfully. Await hospital approval if applicable.";
                    $message_type = "success";
                    $name = "";
                    $email = "";
                    $role = "";
                    $phone = "";
                    $address = "";
                    $city = "";
                    $location = "";
                } else {
                    $message = "Unable to create the hospital profile.";
                    $message_type = "error";
                }

            }

            else {

                $message = "Something went wrong. Please try again.";
                $message_type = "error";

            }


            mysqli_stmt_close($insert_stmt);

        }


        mysqli_stmt_close($check_stmt);

    }

}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Create Account - ImmuniCare</title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >
    <script src="assets/js/form-validation.js" defer></script>

</head>


<body class="register-body">


<main class="register-page">


    <!-- =========================================
         LEFT SIDE - REGISTRATION FORM
    ========================================== -->

    <section class="register-form-side">

        <div class="register-form-container">


            <!-- HOME LINK -->

            <a
                href="index.php"
                class="register-home-link"
            >
                <span>‹</span>
                Home Page
            </a>


            <!-- FORM HEADER -->

            <div class="register-heading">

                <h1>
                    Create Your Account
                </h1>

                <p>
                    Join ImmuniCare and manage your vaccination
                    journey with ease.
                </p>

            </div>


            <!-- MESSAGE -->

            <?php if (!empty($message)) { ?>

                <div
                    class="register-message <?php echo $message_type; ?>"
                    role="alert"
                >
                    <?php echo htmlspecialchars($message); ?>
                </div>

            <?php } ?>


            <!-- REGISTRATION FORM -->

            <form
                method="POST"
                action=""
                class="register-form"
            >
                <?php echo csrf_field(); ?>


                <!-- FULL NAME -->

                <div class="register-field">

                    <label for="name">
                        Full Name
                    </label>

                    <div class="register-input-wrapper">

                        <span class="register-input-icon">
                            ♙
                        </span>

                        <input
                            type="text"
                            id="name"
                            name="name"
                            placeholder="Enter your full name"
                            value="<?php echo htmlspecialchars($name); ?>"
                            maxlength="100"
                            required
                        >

                    </div>

                </div>


                <!-- EMAIL -->

                <div class="register-field">

                    <label for="email">
                        Email Address
                    </label>

                    <div class="register-input-wrapper">

                        <span class="register-input-icon">
                            @
                        </span>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            placeholder="Enter your email address"
                            value="<?php echo htmlspecialchars($email); ?>"
                            maxlength="150"
                            required
                        >

                    </div>

                </div>


                <!-- PASSWORD -->

                <div class="register-field">

                    <label for="password">
                        Password
                    </label>

                    <div class="register-input-wrapper">

                        <span class="register-input-icon">
                            ◈
                        </span>

                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Create a password"
                            minlength="8"
                            maxlength="255"
                            autocomplete="new-password"
                            required
                        >

                    </div>

                </div>


                <!-- CONFIRM PASSWORD -->

                <div class="register-field">

                    <label for="confirm_password">
                        Confirm Password
                    </label>

                    <div class="register-input-wrapper">

                        <span class="register-input-icon">
                            ◈
                        </span>

                        <input
                            type="password"
                            id="confirm_password"
                            name="confirm_password"
                            placeholder="Confirm your password"
                            minlength="8"
                            maxlength="255"
                            autocomplete="new-password"
                            required
                        >

                    </div>

                </div>


                <!-- ACCOUNT TYPE -->

                <div class="register-field">

                    <label for="role">
                        Account Type
                    </label>

                    <div class="register-input-wrapper">

                        <span class="register-input-icon">
                            ♙
                        </span>

                        <select
                            id="role"
                            name="role"
                            required
                        >

                            <option
                                value=""
                                disabled
                                <?php echo empty($role) ? "selected" : ""; ?>
                            >
                                Select account type
                            </option>

                            <option
                                value="parent"
                                <?php echo ($role === "parent") ? "selected" : ""; ?>
                            >
                                Parent
                            </option>

                            <option
                                value="hospital"
                                <?php echo ($role === "hospital") ? "selected" : ""; ?>
                            >
                                Hospital
                            </option>

                        </select>

                        <span class="register-select-arrow">
                            ↓
                        </span>

                    </div>

                </div>

                <div class="register-field hospital-only">
                    <label for="phone">Hospital Phone</label>
                    <div class="register-input-wrapper">
                        <span class="register-input-icon">☎</span>
                        <input type="text" id="phone" name="phone"
                               placeholder="Enter hospital phone"
                               value="<?php echo htmlspecialchars($phone); ?>"
                               maxlength="30"
                               pattern="[0-9+() .-]{7,30}"
                               inputmode="tel">
                    </div>
                </div>

                <div class="register-field hospital-only">
                    <label for="city">Hospital City</label>
                    <div class="register-input-wrapper">
                        <span class="register-input-icon">⌂</span>
                        <input type="text" id="city" name="city"
                               placeholder="Enter hospital city"
                               value="<?php echo htmlspecialchars($city); ?>"
                               maxlength="100">
                    </div>
                </div>

                <div class="register-field hospital-only">
                    <label for="address">Hospital Address</label>
                    <div class="register-input-wrapper register-textarea-wrapper">
                        <span class="register-input-icon">⌖</span>
                        <textarea id="address" name="address" rows="2"
                                  placeholder="Enter hospital address"
                                  maxlength="500"><?php echo htmlspecialchars($address); ?></textarea>
                    </div>
                </div>

                <div class="register-field hospital-only">
                    <label for="location">Hospital Location</label>
                    <div class="register-input-wrapper">
                        <span class="register-input-icon">●</span>
                        <input type="text" id="location" name="location"
                               placeholder="Enter area or location"
                               value="<?php echo htmlspecialchars($location); ?>"
                               maxlength="255">
                    </div>
                </div>


                <!-- TERMS -->

                <label class="register-terms">

                    <input
                        type="checkbox"
                        required
                    >

                    <span>
                        I accept the
                        <a href="terms.php">
                            terms of the agreement
                        </a>
                    </span>

                </label>


                <!-- SUBMIT -->

                <button
                    type="submit"
                    class="register-submit"
                >

                    <span>
                        Sign Up
                    </span>

                    <span class="register-submit-arrow">
                        →
                    </span>

                </button>


            </form>

        </div>

    </section>


    <!-- =========================================
         RIGHT SIDE - GET STARTED
    ========================================== -->

    <section class="register-side">

        <div class="register-side-content">

            <h2>
                Get <span>Started</span>
            </h2>

            <p>
                Already have an account?
            </p>

            <a
                href="login.php"
                class="register-login-button"
            >
                Log in
            </a>


            <div class="register-divider"></div>


            <div class="register-slogan">

                <span>
                    PROTECTING
                </span>

                <strong>
                    BRIGHTER TOMORROWS
                </strong>

            </div>

        </div>

    </section>


</main>

<script>
const accountType = document.getElementById("role");
const hospitalFields = document.querySelectorAll(".hospital-only");

function updateHospitalFields() {
    const show = accountType.value === "hospital";
    hospitalFields.forEach((field) => {
        field.hidden = !show;
        field.querySelectorAll("input, textarea").forEach((input) => {
            input.required = show;
        });
    });
}

accountType.addEventListener("change", updateHospitalFields);
updateHospitalFields();
</script>


</body>

</html>