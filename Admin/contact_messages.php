<?php

require_once "../includes/app.php";

$admin = require_role($conn, "admin");
$admin_id = (int)$admin["id"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verify_csrf();
    $id = post_int("id");
    $status = post_string("status", 20);
    if (in_array($status, ["New", "In Progress", "Resolved", "Spam"], true)) {
        $stmt = $conn->prepare("UPDATE contact_messages SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $id);
        $stmt->execute();
        $stmt->close();
        audit($conn, $admin_id, "contact.status_changed", "contact_message", $id, ["status" => $status]);
    }
    redirect_to("contact_messages.php");
}

$messages = $conn->query(
    "SELECT id, name, email, subject, message, status, created_at
     FROM contact_messages ORDER BY created_at DESC"
);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Contact Messages | ImmuniCare</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="dashboard-body">
<div class="dashboard-layout">
<?php include "sidebar.php"; ?>
<main class="dashboard-main">
    <section class="dashboard-content">
        <div class="section-heading"><h1>Contact Messages</h1><p>Manage support requests.</p></div>
        <div class="users-card">
            <div class="users-table-wrapper">
                <table class="users-table">
                    <thead><tr><th>Sender</th><th>Subject</th><th>Message</th><th>Status</th><th>Action</th></tr></thead>
                    <tbody>
                    <?php while ($message = $messages->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo e($message["name"]); ?><br><?php echo e($message["email"]); ?></td>
                            <td><?php echo e($message["subject"]); ?></td>
                            <td><?php echo e($message["message"]); ?></td>
                            <td><?php echo e($message["status"]); ?></td>
                            <td>
                                <form method="POST">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="id" value="<?php echo (int)$message["id"]; ?>">
                                    <select name="status">
                                        <?php foreach (["New", "In Progress", "Resolved", "Spam"] as $status): ?>
                                            <option value="<?php echo e($status); ?>" <?php echo $status === $message["status"] ? "selected" : ""; ?>><?php echo e($status); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit">Save</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</main>
</div>
</body>
</html>
