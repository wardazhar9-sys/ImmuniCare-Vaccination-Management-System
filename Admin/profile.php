<?php

require_once "../includes/app.php";

$admin = require_role($conn, "admin");
$admin_id = (int)$admin["id"];
$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verify_csrf();
    $name = post_string("name", 100);
    $email = post_string("email", 150);

    if ($name === "" || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Enter a valid name and email.";
        $message_type = "error";
    } else {
        $stmt = $conn->prepare(
            "UPDATE users SET name = ?, email = ? WHERE id = ? AND role = 'admin'"
        );
        $stmt->bind_param("ssi", $name, $email, $admin_id);
        $updated = $stmt->execute();
        $stmt->close();

        $message = $updated ? "Profile updated." : "Unable to update profile.";
        $message_type = $updated ? "success" : "error";
        if ($updated) {
            $_SESSION["name"] = $name;
            $admin["name"] = $name;
            $admin["email"] = $email;
            audit($conn, $admin_id, "profile.updated", "user", $admin_id);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Profile | ImmuniCare</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="dashboard-body">
<div class="dashboard-layout">
<?php include "sidebar.php"; ?>
    <main class="dashboard-main">
        <?php include "../includes/portal_header.php"; ?>
        <section class="dashboard-content">
            <?php if ($message !== ""): ?>
                <div class="appointment-message <?php echo e($message_type); ?>"><?php echo e($message); ?></div>
            <?php endif; ?>
            <div class="dashboard-card">
                <form method="POST">
                    <?php echo csrf_field(); ?>
                    <div class="form-group">
                        <label for="name">Name</label>
                        <input id="name" name="name" value="<?php echo e($admin["name"]); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input id="email" type="email" name="email" value="<?php echo e($admin["email"]); ?>" required>
                    </div>
                    <button class="dashboard-primary-btn" type="submit">Save Changes</button>
                </form>
            </div>
        </section>
    </main>
</div>
</body>
</html>
