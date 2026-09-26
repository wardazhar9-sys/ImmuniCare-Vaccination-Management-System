<?php

require_once "../includes/app.php";

$parent = require_role($conn, "parent");
$parent_id = (int)$parent["id"];

$profile_updated = isset($_GET["updated"]) && $_GET["updated"] == "1";

$password_updated = isset($_GET["password_updated"]) && $_GET["password_updated"] == "1";

// Get parent information
$parent_stmt = $conn->prepare(
    "SELECT id, name, email, role, created_at
     FROM users WHERE id = ? AND role = 'parent'"
);
$parent_stmt->bind_param("i", $parent_id);
$parent_stmt->execute();
$parent = $parent_stmt->get_result()->fetch_assoc();
$parent_stmt->close();

// echo "<pre>";
// print_r($parent);
// echo "</pre>";
// exit();
?>


<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Profile | ImmuniCare</title>

    <link rel="stylesheet" href="../assets/css/style.css">

</head>

<body>

    <div class="parent-dashboard">

        <!-- ================= SIDEBAR ================= -->

        <aside class="dashboard-sidebar">

            <div class="sidebar-brand">

                <img
                    src="../assets/images/immunicare-logo-sidebar.svg"
                    alt="ImmuniCare Parent Portal"
                    class="sidebar-brand-image"
                >

            </div>

            <!-- Navigation -->

            <nav class="sidebar-nav">

                <div class="nav-section-title">
                    MAIN MENU
                </div>

                <a href="dashboard.php" class="sidebar-link">
                    <span class="sidebar-icon">⌂</span>
                    <span>Dashboard</span>
                </a>

                <a href="children.php" class="sidebar-link">
                    <span class="sidebar-icon">♙</span>
                    <span>My Children</span>
                </a>

                <a href="vaccines.php" class="sidebar-link">
                    <span class="sidebar-icon">✚</span>
                    <span>Vaccines</span>
                </a>

                <a href="schedule.php" class="sidebar-link">
                    <span class="sidebar-icon">▣</span>
                    <span>Vaccination Schedule</span>
                </a>

                <div class="nav-section-title dashboard-nav-spacing">
                    APPOINTMENTS
                </div>

                <a href="book_appointment.php" class="sidebar-link">
                    <span class="sidebar-icon">＋</span>
                    <span>Book Appointment</span>
                </a>

                <a href="bookings.php" class="sidebar-link">
                    <span class="sidebar-icon">▤</span>
                    <span>My Bookings</span>
                </a>

                <div class="nav-section-title dashboard-nav-spacing">
                    HEALTH RECORDS
                </div>

                <a href="vaccination_history.php" class="sidebar-link">
                    <span class="sidebar-icon">✓</span>
                    <span>Vaccination History</span>
                </a>

                <a href="profile.php" class="sidebar-link active">
                    <span class="sidebar-icon">◯</span>
                    <span>My Profile</span>
                </a>

            </nav>

            <!-- Sidebar Bottom -->

            <div class="sidebar-bottom">

                <a href="logout.php" class="logout-link">
                    <span class="sidebar-icon">↪</span>
                    <span>Logout</span>
                </a>

            </div>

        </aside>


        <!-- ================= MAIN CONTENT ================= -->

        <main class="dashboard-main">

            <!-- TOP HEADER -->

            <header class="dashboard-header">

                <div class="header-page-title">

                    <h1>My Profile</h1>

                    <p>
                        Manage your account and personal information
                    </p>

                </div>


                <div class="header-actions">

                    <button class="notification-button" type="button">
                        <span>♢</span>
                        <span class="notification-dot"></span>
                    </button>

                    <div class="header-divider"></div>

                    <div class="profile-mini">

                        <div class="profile-avatar">
                            <?php echo strtoupper(substr($parent["name"], 0, 1)); ?>
                        </div>

                        <div class="profile-info">

                            <strong>
                                <?php echo htmlspecialchars($parent["name"]); ?>
                            </strong>

                            <span>Parent Account</span>

                        </div>

                    </div>

                </div>

            </header>


            <!-- PROFILE CONTENT -->

            <section class="dashboard-content">

            <?php if ($profile_updated): ?>

    <div class="profile-success-message">
        Profile updated successfully.
    </div>

<?php endif; ?>

<?php if ($password_updated): ?>

    <div class="profile-success-message">
        Password changed successfully.
    </div>

<?php endif; ?>

                <!-- PROFILE OVERVIEW -->

                <div class="profile-overview-card">

                    <div class="profile-large-avatar">

                        <?php echo strtoupper(substr($parent["name"], 0, 1)); ?>

                    </div>

                    <div class="profile-overview-info">

                        <h2>
                            <?php echo htmlspecialchars($parent["name"]); ?>
                        </h2>

                        <p>
                            <?php echo htmlspecialchars($parent["email"]); ?>
                        </p>

                        <span class="profile-status">
                            ● Active
                        </span>

                    </div>

                </div>


                <!-- PERSONAL INFORMATION -->

                <div class="profile-section-card">

                    <div class="profile-section-header">

                        <div>

                            <h2>Personal Information</h2>

                            <p>
                                Your account information
                            </p>

                        </div>

                    </div>


                    <div class="profile-information-grid">

                        <div class="profile-information-item">

                            <span class="profile-information-label">
                                Full Name
                            </span>

                            <strong>
                                <?php echo htmlspecialchars($parent["name"]); ?>
                            </strong>

                        </div>


                        <div class="profile-information-item">

                            <span class="profile-information-label">
                                Email Address
                            </span>

                            <strong>
                                <?php echo htmlspecialchars($parent["email"]); ?>
                            </strong>

                        </div>


                        <div class="profile-information-item">

                            <span class="profile-information-label">
                                Account Type
                            </span>

                            <strong>
                                Parent
                            </strong>

                        </div>


                        <div class="profile-information-item">

                            <span class="profile-information-label">
                                Member Since
                            </span>

                            <strong>
                                <?php echo date("F d, Y", strtotime($parent["created_at"])); ?>
                            </strong>

                        </div>

                    </div>


                <div class="profile-section-action">
    <a href="edit_profile.php" class="dashboard-primary-btn">
        Edit Profile
    </a>
</div>

                </div>


                <!-- ACCOUNT SECURITY -->

                <div class="profile-section-card">

                    <div class="profile-section-header">

                        <div>

                            <h2>Account Security</h2>

                            <p>
                                Keep your ImmuniCare account secure
                            </p>

                        </div>

                    </div>


                    <div class="security-row">

                        <div class="security-icon">
                            🔒
                        </div>

                        <div class="security-information">

                            <strong>Password</strong>

                            <span>
                                Your password is securely protected
                            </span>

                        </div>

<a href="change_password.php" class="dashboard-primary-btn">
    Change Password
</a>

                    </div>

                </div>

            </section>

        </main>

    </div>

</body>

</html>