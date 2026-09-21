<?php

include("config/db.php");

$message = "";
$message_type = "";

$name = "";
$email = "";
$password = "";
$confirm_password = "";
$role = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Get form data
    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];
    $role = $_POST["role"];


    // Check if all fields are filled
    if (
        empty($name) ||
        empty($email) ||
        empty($password) ||
        empty($confirm_password) ||
        empty($role)
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
    elseif (strlen($password) < 6) {

        $message = "Password must be at least 6 characters long.";
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


            if (mysqli_stmt_execute($insert_stmt)) {

                $message = "Account created successfully! You can now login.";
                $message_type = "success";

                // Clear name and email after successful registration
                $name = "";
                $email = "";
                $role = "";

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
                href="index_old.php"
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


                <!-- TERMS -->

                <label class="register-terms">

                    <input
                        type="checkbox"
                        required
                    >

                    <span>
                        I accept the
                        <a href="#" onclick="return false;">
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


</body>

</html>