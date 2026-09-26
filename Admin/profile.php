<?php

require_once "../includes/app.php";

$admin = require_role($conn, "admin");
$admin_id = (int)$admin["id"];
$message = "";
$message_type = "";

$details_stmt = $conn->prepare(
    "SELECT name, email, role, status, created_at, email_verified_at
     FROM users WHERE id = ? LIMIT 1"
);
$details_stmt->bind_param("i", $admin_id);
$details_stmt->execute();
$admin_details = $details_stmt->get_result()->fetch_assoc();
$details_stmt->close();

$stats = [];
foreach ([
    "users" => "SELECT COUNT(*) total FROM users",
    "hospitals" => "SELECT COUNT(*) total FROM hospitals",
    "children" => "SELECT COUNT(*) total FROM children WHERE archived_at IS NULL",
    "records" => "SELECT COUNT(*) total FROM vaccination_records"
] as $key => $query) {
    $stats[$key] = (int)$conn->query($query)->fetch_assoc()["total"];
}

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
            <div class="profile-overview-card">
                <div class="profile-large-avatar"><?php echo e(strtoupper(substr($admin_details["name"], 0, 1))); ?></div>
                <div class="profile-overview-info">
                    <h2><?php echo e($admin_details["name"]); ?></h2>
                    <p><?php echo e($admin_details["email"]); ?></p>
                    <span class="profile-status">● <?php echo e($admin_details["status"]); ?></span>
                </div>
            </div>

            <div class="dashboard-stats profile-stat-grid">
                <div class="dashboard-stat-card"><div class="stat-icon stat-icon-blue">♧</div><div class="stat-information"><span class="stat-label">Users</span><strong class="stat-number"><?php echo $stats["users"]; ?></strong><span class="stat-description">Managed accounts</span></div></div>
                <div class="dashboard-stat-card"><div class="stat-icon stat-icon-green">♜</div><div class="stat-information"><span class="stat-label">Hospitals</span><strong class="stat-number"><?php echo $stats["hospitals"]; ?></strong><span class="stat-description">Registered facilities</span></div></div>
                <div class="dashboard-stat-card"><div class="stat-icon stat-icon-orange">♙</div><div class="stat-information"><span class="stat-label">Children</span><strong class="stat-number"><?php echo $stats["children"]; ?></strong><span class="stat-description">Active profiles</span></div></div>
                <div class="dashboard-stat-card"><div class="stat-icon stat-icon-purple">✓</div><div class="stat-information"><span class="stat-label">Records</span><strong class="stat-number"><?php echo $stats["records"]; ?></strong><span class="stat-description">Vaccination records</span></div></div>
            </div>

            <div class="profile-section-card">
                <div class="profile-section-header"><div><h2>Account Information</h2><p>Administrator access and account metadata.</p></div></div>
                <div class="profile-information-grid">
                    <div class="profile-information-item"><span class="profile-information-label">Role</span><strong><?php echo e(ucfirst($admin_details["role"])); ?></strong></div>
                    <div class="profile-information-item"><span class="profile-information-label">Status</span><strong><?php echo e($admin_details["status"]); ?></strong></div>
                    <div class="profile-information-item"><span class="profile-information-label">Member Since</span><strong><?php echo e(format_date_value($admin_details["created_at"])); ?></strong></div>
                    <div class="profile-information-item"><span class="profile-information-label">Email Verification</span><strong><?php echo $admin_details["email_verified_at"] ? "Verified" : "Pending"; ?></strong></div>
                </div>
            </div>

            <div class="profile-section-card">
                <div class="profile-section-header"><div><h2>Personal Information</h2><p>Update the administrator name and email address.</p></div></div>
                <form method="POST">
                    <?php echo csrf_field(); ?>
                    <div class="form-group">
                        <label for="name">Name</label>
                        <input id="name" name="name" value="<?php echo e($admin_details["name"]); ?>" maxlength="100" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input id="email" type="email" name="email" value="<?php echo e($admin_details["email"]); ?>" maxlength="150" required>
                    </div>
                    <button class="dashboard-primary-btn" type="submit">Save Changes</button>
                </form>
            </div>
        </section>
    </main>
</div>
</body>
</html>
