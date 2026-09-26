<?php

require_once "includes/app.php";

$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    verify_csrf();

    $email = post_string("email", 150);
    $password = (string)($_POST["password"] ?? "");

    // Check whether fields are empty
    if (empty($email) || empty($password)) {

        $message = "Please enter your email and password.";
        $message_type = "error";
    }

    // Check email format
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "Please enter a valid email address.";
        $message_type = "error";
    }

    else {

        // Find user by email
        $sql = "SELECT id, name, password, role, status FROM users WHERE email = ?";

        $stmt = mysqli_prepare($conn, $sql);

        mysqli_stmt_bind_param($stmt, "s", $email);

        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) == 1) {

            $user = mysqli_fetch_assoc($result);

            // Check password
            if (
                password_verify($password, $user["password"])
                && ($user["status"] ?? "Active") === "Active"
            ) {

                // Create session
                session_regenerate_id(true);
                $_SESSION["user_id"] = $user["id"];
                $_SESSION["name"] = $user["name"];
                $_SESSION["role"] = $user["role"];

                 // Send user to the correct dashboard
                 if ($user["role"] == "parent"){
                    header("Location: Parent/dashboard.php");
                    exit();
                 }

                 elseif($user["role"] == "hospital"){
                    header("Location: Hospital/dashboard.php");
                    exit();
                 }

                 elseif ($user["role"] == "admin"){
                     header("Location: Admin/dashboard.php");
                      exit();
                 }

            //     $message = "Login successful!";
            //     $message_type = "success";
            // 
            }

            else {

                $message = "Incorrect email or password.";
                $message_type = "error";
            }
        }

        else {

            $message = "Incorrect email or password.";
            $message_type = "error";
        }

        mysqli_stmt_close($stmt);
    }
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login - ImmuniCare</title>

    <link rel="stylesheet" href="assets/css/style.css">

</head>

<body class="login-body">

<div class="login-page">

    <!-- LEFT BLUE PANEL -->

    <div class="login-side">

        <div class="login-side-content">

            <h2>
                Welcome <span>Back</span>
            </h2>

            <p>
                Login to continue your ImmuniCare journey.
            </p>

            <div class="login-side-line"></div>

            <div class="login-tagline">
                <span>PROTECTING</span>
                <strong>BRIGHTER TOMORROWS</strong>
            </div>

        </div>

    </div>


    <!-- RIGHT LOGIN FORM -->

    <div class="login-form-side">

        <div class="login-form-container">

            <a href="index.php" class="login-home-link">
                <span>‹</span>
                Home Page
            </a>


            <div class="login-heading">

                <h1>Welcome Back</h1>

                <p>
                    Login to your ImmuniCare account
                </p>

            </div>


            <?php if (!empty($message)) { ?>

                <div
                    class="message toast <?php echo htmlspecialchars($message_type); ?>"
                    role="alert"
                >
                    <?php echo htmlspecialchars($message); ?>
                </div>

            <?php } ?>


            <form method="POST" action="">
                <?php echo csrf_field(); ?>

                <!-- EMAIL -->

                <div class="login-field">

                    <label for="email">
                        Email Address
                    </label>

                    <div class="login-input-wrapper">

                        <span class="login-input-icon">
                            ✉
                        </span>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            placeholder="Enter your email address"
                            value="<?php echo htmlspecialchars($email ?? ''); ?>"
                            required
                        >

                    </div>

                </div>


                <!-- PASSWORD -->

                <div class="login-field">

                    <label for="password">
                        Password
                    </label>

                    <div class="login-input-wrapper">

                        <span class="login-input-icon">
                            ◆
                        </span>

                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Enter your password"
                            required
                        >

                    </div>

                </div>


                <!-- LOGIN BUTTON -->

                <button
                    type="submit"
                    class="login-submit"
                >
                    Log In
                    <span>→</span>
                </button>

            </form>

            <p><a href="forgot_password.php">Forgot password?</a></p>


            <div class="login-footer">

                <p>
                    Don't have an account?

                    <a href="register.php">
                        Create an account
                    </a>
                </p>

            </div>

        </div>

    </div>

</div>

</body>

</html>