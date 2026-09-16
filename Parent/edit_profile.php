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

if (isset($_POST["update_profile"])) {
      $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);

    // Check whether the email format is valid
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error_message = "Please enter a valid email address.";

    } else {

        // Check whether another user already has this email
        $email_check_query = "SELECT id
                              FROM users
                              WHERE email = '$email'
                              AND id != '$parent_id'";

        $email_check_result = mysqli_query($conn, $email_check_query);

        if (mysqli_num_rows($email_check_result) > 0) {

            $error_message = "This email address is already being used by another account.";

        } else {

            // Update profile information
            $update_query = "UPDATE users
                             SET name = '$name',
                                 email = '$email'
                             WHERE id = '$parent_id'
                             AND role = 'parent'";

            if (mysqli_query($conn, $update_query)) {

                // Update the session name as well
                $_SESSION["name"] = $name;

                header("Location: profile.php?updated=1");
                exit();

            } else {

                $error_message = "Error updating profile: " . mysqli_error($conn);
            }
        }
    }
}

// Get current parent information
$parent_query = "SELECT name, email
                 FROM users
                 WHERE id = '$parent_id'
                 AND role = 'parent'";

$parent_result = mysqli_query($conn, $parent_query);
$parent = mysqli_fetch_assoc($parent_result);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit Profile | ImmuniCare</title>

    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>

    <div class="parent-dashboard">

        <main class="dashboard-main">

            <header class="dashboard-header">

                <div class="header-page-title">
                    <h1>Edit Profile</h1>
                    <p>Update your personal information</p>
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
                            <h2>Personal Information</h2>
                            <p>Update your account details</p>
                        </div>
                    </div>

                    <form method="POST" action="">

                        <div class="profile-information-grid">

                            <div class="profile-information-item">

                                <label class="profile-information-label">
                                    Full Name
                                </label>

                                <input
                                    type="text"
                                    name="name"
                                    value="<?php echo htmlspecialchars($parent["name"]); ?>"
                                    required
                                >

                            </div>

                            <div class="profile-information-item">

                                <label class="profile-information-label">
                                    Email Address
                                </label>

                                <input
                                    type="email"
                                    name="email"
                                    value="<?php echo htmlspecialchars($parent["email"]); ?>"
                                    required
>

                            </div>

                        </div>

                        <div class="profile-section-action">

                            <button
                                type="submit"
                                name="update_profile"
                                class="dashboard-primary-btn"
                            >
                                Save Changes
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