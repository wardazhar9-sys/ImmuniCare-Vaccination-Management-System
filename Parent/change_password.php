<?php

session_start();

include("../config/db.php");

// Check whether user is logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit();
}

// Check whether the logged-in user is a parent
if ($_SESSION["role"] !== "parent") {
    header("Location: ../login.php");
    exit();
}

$parent_id = $_SESSION["user_id"];

// Change password
if (isset($_POST["change_password"])) {

    $current_password = $_POST["current_password"];
    $new_password = $_POST["new_password"];
    $confirm_password = $_POST["confirm_password"];

    // Get the current password from the database
    $password_query = "SELECT password
                       FROM users
                       WHERE id = '$parent_id'
                       AND role = 'parent'";

    $password_result = mysqli_query($conn, $password_query);
    $user = mysqli_fetch_assoc($password_result);

    // Check current password
    if (!password_verify($current_password, $user["password"])) {

        $error_message = "Your current password is incorrect.";

    // Check whether new passwords match
    } elseif ($new_password !== $confirm_password) {

        $error_message = "The new passwords do not match.";

    // Check minimum password length
    } elseif (strlen($new_password) < 8) {

        $error_message = "Your new password must be at least 8 characters long.";

    } else {

        // Securely hash the new password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        // Update password in database
        $update_password_query = "UPDATE users
                                  SET password = '$hashed_password'
                                  WHERE id = '$parent_id'
                                  AND role = 'parent'";

        if (mysqli_query($conn, $update_password_query)) {

            header("Location: profile.php?password_updated=1");
            exit();

        } else {

            $error_message = "Error updating password: " . mysqli_error($conn);
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Change Password | ImmuniCare</title>

    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>

    <div class="parent-dashboard">

        <main class="dashboard-main">

            <header class="dashboard-header">

                <div class="header-page-title">
                    <h1>Change Password</h1>
                    <p>Update your ImmuniCare account password</p>
                </div>

            </header>

            <section class="dashboard-content">

            <?php if (isset($error_message)): ?>

    <div class="profile-error-message">
        <?php echo htmlspecialchars($error_message); ?>
    </div>

<?php endif; ?>

                <div class="profile-section-card">

                    <div class="profile-section-header">
                        <div>
                            <h2>Change Password</h2>
                            <p>Enter your current password and choose a new one</p>
                        </div>
                    </div>

                    <form method="POST" action="">

                        <div class="profile-information-grid">

                            <div class="profile-information-item">

                                <label class="profile-information-label">
                                    Current Password
                                </label>

                                <input
                                    type="password"
                                    name="current_password"
                                    required
                                >

                            </div>

                            <div class="profile-information-item">

                                <label class="profile-information-label">
                                    New Password
                                </label>

                                <input
                                    type="password"
                                    name="new_password"
                                    required
                                >

                            </div>

                            <div class="profile-information-item">

                                <label class="profile-information-label">
                                    Confirm New Password
                                </label>

                                <input
                                    type="password"
                                    name="confirm_password"
                                    required
                                >

                            </div>

                        </div>

                        <div class="profile-section-action">

                            <button
                                type="submit"
                                name="change_password"
                                class="dashboard-primary-btn"
                            >
                                Change Password
                            </button>

                            <a
                                href="profile.php"
                                class="secondary-profile-btn"
                            >
                                Cancel
                            </a>

                        </div>

                    </form>

                </div>

            </section>

        </main>

    </div>

</body>

</html>