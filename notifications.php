<?php

require_once "includes/app.php";

$user = current_user($conn);
if (!$user) {
    redirect_to("login.php");
}

$user_id = (int)$user["id"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verify_csrf();
    $notification_id = post_int("notification_id");

    if ($notification_id > 0) {
        $stmt = $conn->prepare(
            "UPDATE notifications SET is_read = 1
             WHERE id = ? AND user_id = ?"
        );
        $stmt->bind_param("ii", $notification_id, $user_id);
    } else {
        $stmt = $conn->prepare(
            "UPDATE notifications SET is_read = 1 WHERE user_id = ?"
        );
        $stmt->bind_param("i", $user_id);
    }

    $stmt->execute();
    $stmt->close();
    redirect_to("notifications.php");
}

$stmt = $conn->prepare(
    "SELECT id, title, message, type, is_read, created_at, link_url
     FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50"
);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$notifications = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Notifications | ImmuniCare</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="dashboard-body">
<main class="dashboard-main notification-page">
    <section class="dashboard-content">
        <div class="section-heading">
            <h1>Notifications</h1>
            <p>Updates about appointments, schedules, accounts, and records.</p>
        </div>
        <form method="POST">
            <?php echo csrf_field(); ?>
            <button class="dashboard-primary-btn" type="submit">Mark all as read</button>
        </form>
        <div class="notification-list">
            <?php while ($notification = $notifications->fetch_assoc()): ?>
                <article class="notification-card <?php echo $notification["is_read"] ? "read" : "unread"; ?>">
                    <div>
                        <strong><?php echo e($notification["title"]); ?></strong>
                        <p><?php echo e($notification["message"]); ?></p>
                        <small><?php echo e($notification["created_at"]); ?></small>
                    </div>
                    <div>
                        <?php if (!empty($notification["link_url"])): ?>
                            <a class="dashboard-primary-btn" href="<?php echo e($notification["link_url"]); ?>">Open</a>
                        <?php endif; ?>
                        <?php if (!$notification["is_read"]): ?>
                            <form method="POST">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="notification_id" value="<?php echo (int)$notification["id"]; ?>">
                                <button type="submit">Mark read</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endwhile; ?>
        </div>
    </section>
</main>
</body>
</html>
