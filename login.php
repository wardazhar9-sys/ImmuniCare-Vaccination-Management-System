
<?php

session_start();

include("config/db.php");

$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST["email"]);
    $password = $_POST["password"];

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
        $sql = "SELECT id, name, password, role FROM users WHERE email = ?";

        $stmt = mysqli_prepare($conn, $sql);

        mysqli_stmt_bind_param($stmt, "s", $email);

        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) == 1) {

            $user = mysqli_fetch_assoc($result);

            // Check password
            if (password_verify($password, $user["password"])) {

                // Create session
                $_SESSION["user_id"] = $user["id"];
                $_SESSION["name"] = $user["name"];
                $_SESSION["role"] = $user["role"];

                 // Send user to the correct dashboard
                 if ($user["role"] == "parent"){
                    header("Location: Parent/dashboard.php");
                    exit();
                 }

                 elseif($user["role"] == "hospital"){
                    header("Location: hospital/dashboard.php");
                    exit();
                 }

                 elseif ($user["role"] == "admin"){
                     header("Location: admin/dashboard.php");
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

<body>

<?php
include("includes/navbar.php");
?>

<div class="auth-page">

    <div class="auth-container">

        <div class="auth-card">

            <div class="auth-header">

                <h1>Welcome Back</h1>

                <p>Login to your ImmuniCare account</p>

            </div>


            <?php if (!empty($message)) { ?>

                <div class="message toast <?php echo htmlspecialchars($message_type); ?>" role="alert">

                    <?php echo htmlspecialchars($message); ?>

                </div>

            <?php } ?>


            <form method="POST" action="">

                <div class="form-group">

                    <label for="email">Email Address</label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="Enter your email address"
                        value="<?php echo htmlspecialchars($email ?? ''); ?>"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="password">Password</label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Enter your password"
                        required
                    >

                </div>


                <button type="submit" class="auth-btn">
                    Login
                </button>

            </form>


            <div class="auth-footer">

                <p>

                    Don't have an account?

                    <a href="register.php">Create an account</a>

                </p>

            </div>

        </div>

    </div>

</div>

</body>

</html>

