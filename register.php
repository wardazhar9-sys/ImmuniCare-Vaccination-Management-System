<?php

include("config/db.php");

$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Get form data
    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];
    $role = $_POST["role"];


    // Check if all fields are filled
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password) || empty($role)) {

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

        mysqli_stmt_bind_param($check_stmt, "s", $email);

        mysqli_stmt_execute($check_stmt);

        mysqli_stmt_store_result($check_stmt);


        if (mysqli_stmt_num_rows($check_stmt) > 0) {

            $message = "An account with this email already exists.";
            $message_type = "error";

        }

        else {

            // Hash the password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);


            // Insert user into users table
            $insert_sql = "INSERT INTO users (name, email, password, role)
                           VALUES (?, ?, ?, ?)";

            $insert_stmt = mysqli_prepare($conn, $insert_sql);

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

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Create Account - ImmuniCare</title>

    <link rel="stylesheet" href="assets/css/style.css">

</head>


<body>

<?php include("includes/navbar.php"); ?>


<main class="auth-page">

    <div class="auth-container">

        <div class="auth-card">

            <div class="auth-header">

                <h1>Create Your Account</h1>

                <p>
                    Join ImmuniCare and manage your vaccination journey with ease.
                </p>

            </div>


            <?php

            if (!empty($message)) {

                echo '<div class="message toast ' . $message_type . '" role="alert">';
                echo htmlspecialchars($message);
                echo '</div>';

            }

            ?>


            <form method="POST" action="">


                <div class="form-group">

                    <label for="name">
                        Full Name
                    </label>

                    <input
                        type="text"
                        id="name"
                        name="name"
                        placeholder="Enter your full name"
                        value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="email">
                        Email Address
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="Enter your email address"
                        value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="password">
                        Password
                    </label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Create a password"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="confirm_password">
                        Confirm Password
                    </label>

                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        placeholder="Confirm your password"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>
                        Account Type
                    </label>

                    <div class="role-options">

                        <label class="role-option">

                            <input
                                type="radio"
                                name="role"
                                value="parent"
                                required
                            >

                            <span>Parent</span>

                        </label>


                        <label class="role-option">

                            <input
                                type="radio"
                                name="role"
                                value="hospital"
                            >

                            <span>Hospital</span>

                        </label>

                    </div>

                </div>


                <button type="submit" class="auth-btn">
                    Create Account
                </button>


            </form>


            <div class="auth-footer">

                <p>
                    Already have an account?
                    <a href="login.php">Login</a>
                </p>

            </div>


        </div>

    </div>

</main>


</body>

</html>
