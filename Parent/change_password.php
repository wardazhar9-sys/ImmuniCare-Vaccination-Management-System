<?php
require_once "../includes/app.php";

$user = require_role($conn, "parent");
$parent_id = (int)$user["id"];
$error_message = "";

if (isset($_POST["change_password"])) {
    verify_csrf();
    $current = (string)($_POST["current_password"] ?? "");
    $new = (string)($_POST["new_password"] ?? "");
    $confirm = (string)($_POST["confirm_password"] ?? "");

    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ? AND role = 'parent'");
    $stmt->bind_param("i", $parent_id);
    $stmt->execute();
    $stored = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$stored || !password_verify($current, $stored["password"])) {
        $error_message = "Your current password is incorrect.";
    } elseif (strlen($new) < 8 || $new !== $confirm) {
        $error_message = "Use an 8-character password and confirm it correctly.";
    } else {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ? AND role = 'parent'");
        $stmt->bind_param("si", $hash, $parent_id);
        $updated = $stmt->execute();
        $stmt->close();

        if ($updated) {
            audit($conn, $parent_id, "password.changed", "user", $parent_id);
            redirect_to("profile.php?password_updated=1");
        }

        $error_message = "Unable to change your password.";
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
        <?php include "sidebar.php"; ?>
        <main class="dashboard-main">

            <?php include "../includes/portal_header.php"; ?>

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
                        <?php echo csrf_field(); ?>

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