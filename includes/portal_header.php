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
$portal_notifications = [];

if (isset($conn, $_SESSION["user_id"])) {
    $portal_notification_stmt = $conn->prepare(
        "SELECT COUNT(*) AS total FROM notifications WHERE user_id = ? AND is_read = 0"
    );
    $portal_user_id = (int)$_SESSION["user_id"];
    $portal_notification_stmt->bind_param("i", $portal_user_id);
    $portal_notification_stmt->execute();
    $portal_unread = (int)$portal_notification_stmt->get_result()->fetch_assoc()["total"];
    $portal_notification_stmt->close();

    $portal_list_stmt = $conn->prepare(
        "SELECT id, title, message, is_read, created_at, link_url
         FROM notifications WHERE user_id = ?
         ORDER BY created_at DESC LIMIT 8"
    );
    $portal_list_stmt->bind_param("i", $portal_user_id);
    $portal_list_stmt->execute();
    $portal_list_result = $portal_list_stmt->get_result();
    while ($portal_notification = $portal_list_result->fetch_assoc()) {
        $portal_notifications[] = $portal_notification;
    }
    $portal_list_stmt->close();
}
?>
<header class="dashboard-header">
    <div class="header-page-title">
        <h1><?php echo e($portal_title); ?></h1>
        <p><?php echo e($portal_subtitle); ?></p>
    </div>
    <div class="header-actions">
        <button type="button" class="notification-button" aria-label="Notifications" aria-controls="portalNotificationModal" aria-expanded="false" data-notification-toggle>
            <svg class="notification-bell" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"></path>
                <path d="M10 21h4"></path>
            </svg>
            <?php if ($portal_unread > 0): ?><span class="notification-dot"></span><?php endif; ?>
        </button>
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

<div class="portal-notification-modal" id="portalNotificationModal" aria-hidden="true">
    <div class="portal-notification-backdrop" data-notification-close></div>
    <section class="portal-notification-panel" role="dialog" aria-modal="true" aria-labelledby="portalNotificationTitle">
        <div class="portal-notification-panel-header">
            <h2 id="portalNotificationTitle">Notifications</h2>
            <button type="button" aria-label="Close notifications" data-notification-close>×</button>
        </div>
        <div class="portal-notification-list">
            <?php if (!$portal_notifications): ?>
                <p class="notification-empty">No notifications yet.</p>
            <?php else: ?>
                <?php foreach ($portal_notifications as $notification): ?>
                    <article class="portal-notification-item <?php echo $notification["is_read"] ? "read" : "unread"; ?>">
                        <div>
                            <strong><?php echo e($notification["title"]); ?></strong>
                            <p><?php echo e($notification["message"]); ?></p>
                            <small><?php echo e($notification["created_at"]); ?></small>
                        </div>
                        <div class="portal-notification-actions">
                            <?php if (!empty($notification["link_url"])): ?>
                                <a href="../<?php echo e($notification["link_url"]); ?>">Open</a>
                            <?php endif; ?>
                            <?php if (!$notification["is_read"]): ?>
                                <form method="POST" action="../notifications.php" data-notification-form>
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="notification_id" value="<?php echo (int)$notification["id"]; ?>">
                                    <button type="submit">Mark read</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php if ($portal_unread > 0): ?>
            <form method="POST" action="../notifications.php" data-notification-form>
                <?php echo csrf_field(); ?>
                <button class="portal-notification-mark-all" type="submit">Mark all as read</button>
            </form>
        <?php endif; ?>
    </section>
</div>

<script>
(() => {
    const modal = document.getElementById("portalNotificationModal");
    const toggle = document.querySelector("[data-notification-toggle]");
    if (!modal || !toggle) return;
    const close = () => {
        modal.classList.remove("show");
        modal.setAttribute("aria-hidden", "true");
        toggle.setAttribute("aria-expanded", "false");
    };
    toggle.addEventListener("click", () => {
        const open = modal.classList.toggle("show");
        modal.setAttribute("aria-hidden", open ? "false" : "true");
        toggle.setAttribute("aria-expanded", open ? "true" : "false");
    });
    modal.querySelectorAll("[data-notification-close]").forEach((button) => {
        button.addEventListener("click", close);
    });
    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape") close();
    });
    modal.querySelectorAll("[data-notification-form]").forEach((form) => {
        form.addEventListener("submit", async (event) => {
            event.preventDefault();
            await fetch(form.action, {
                method: "POST",
                body: new URLSearchParams(new FormData(form)),
                credentials: "same-origin"
            });
            window.location.reload();
        });
    });
})();
</script>
