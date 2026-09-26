<?php

$portal_role = $_SESSION["role"] ?? "user";
$portal_page = basename($_SERVER["PHP_SELF"]);
$portal_name = $_SESSION["name"] ?? "Account";
$portal_meta = [
    "parent" => [
        "dashboard.php" => ["Dashboard", "Manage your children's vaccination journey"],
        "children.php" => ["My Children", "View and manage your registered children"],
        "vaccines.php" => ["Vaccines", "Explore vaccines available through ImmuniCare"],
        "schedule.php" => ["Vaccination Schedule", "View your children's upcoming vaccination schedules"],
        "book_appointment.php" => ["Book Appointment", "Schedule a vaccination appointment for your child"],
        "bookings.php" => ["My Bookings", "View and manage your vaccination appointments"],
        "vaccination_history.php" => ["Vaccination History", "View your children's completed vaccination records"],
        "profile.php" => ["My Profile", "Manage your account and personal information"],
        "edit_profile.php" => ["Edit Profile", "Update your personal information"],
        "change_password.php" => ["Change Password", "Update your ImmuniCare account password"],
        "edit_child.php" => ["Edit Child", "Update your child's information"]
    ],
    "hospital" => [
        "dashboard.php" => ["Dashboard", "Manage your hospital's vaccination activities"],
        "appointments.php" => ["Appointments", "Manage vaccination appointments"],
        "schedule.php" => ["Vaccination Schedule", "Schedule approved vaccination appointments"],
        "slots.php" => ["Appointment Slots", "Manage your hospital's available appointment times"],
        "vaccinations.php" => ["Vaccinations", "Record and manage children's vaccination records"],
        "profile.php" => ["My Profile", "View and manage your hospital profile information"]
    ],
    "admin" => [
        "dashboard.php" => ["Dashboard", "Manage the ImmuniCare vaccination system"],
        "users.php" => ["Users", "Manage registered users in the ImmuniCare system"],
        "children.php" => ["Children", "Manage registered children in the ImmuniCare system"],
        "hospitals.php" => ["Hospitals", "Manage registered hospitals in the ImmuniCare system"],
        "vaccines.php" => ["Vaccines", "Manage vaccines available in the ImmuniCare system"],
        "vaccine_doses.php" => ["Vaccine Doses", "Manage dose definitions and clinical sources"],
        "inventory.php" => ["Inventory", "Manage hospital vaccine inventory"],
        "reports.php" => ["Reports", "Export vaccination system activity"],
        "contact_messages.php" => ["Contact Messages", "Manage support requests"],
        "bookings.php" => ["Bookings", "Manage vaccination appointments across ImmuniCare"],
        "schedules.php" => ["Vaccination Schedule", "Manage children's vaccination schedules"],
        "vaccination_records.php" => ["Vaccination Records", "Review audited vaccination records"],
        "profile.php" => ["Admin Profile", "Manage administrator account details"]
    ]
];
$portal_title = $portal_meta[$portal_role][$portal_page][0] ?? "ImmuniCare";
$portal_subtitle = $portal_meta[$portal_role][$portal_page][1] ?? "Vaccination management portal";
$portal_unread = 0;

if (isset($conn, $_SESSION["user_id"])) {
    $portal_notification_stmt = $conn->prepare(
        "SELECT COUNT(*) AS total FROM notifications WHERE user_id = ? AND is_read = 0"
    );
    $portal_user_id = (int)$_SESSION["user_id"];
    $portal_notification_stmt->bind_param("i", $portal_user_id);
    $portal_notification_stmt->execute();
    $portal_unread = (int)$portal_notification_stmt->get_result()->fetch_assoc()["total"];
    $portal_notification_stmt->close();
}
?>
<header class="dashboard-header">
    <div class="header-page-title">
        <h1><?php echo e($portal_title); ?></h1>
        <p><?php echo e($portal_subtitle); ?></p>
    </div>
    <div class="header-actions">
        <a href="../notifications.php" class="notification-button" aria-label="Notifications">
            <span aria-hidden="true">♢</span>
            <?php if ($portal_unread > 0): ?><span class="notification-dot"></span><?php endif; ?>
        </a>
        <div class="header-divider"></div>
        <div class="profile-mini">
            <div class="profile-avatar"><?php echo e(strtoupper(substr($portal_name, 0, 1))); ?></div>
            <div class="profile-info">
                <strong><?php echo e($portal_name); ?></strong>
                <span><?php echo e(ucfirst($portal_role)); ?> Account</span>
            </div>
        </div>
    </div>
</header>
