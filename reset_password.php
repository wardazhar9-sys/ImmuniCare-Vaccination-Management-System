<?php

require_once "includes/app.php";

$token = (string)($_GET["token"] ?? $_POST["token"] ?? "");
$message = "";
$success = false;

if (strlen($token) !== 64) {
    $message = "This recovery link is invalid or expired.";
} elseif ($_SERVER["REQUEST_METHOD"] === "POST") {
    verify_csrf();
    $password = (string)($_POST["password"] ?? "");
    $confirm = (string)($_POST["confirm_password"] ?? "");

    if (strlen($password) < 8 || $password !== $confirm) {
        $message = "Use an 8-character password and confirm it correctly.";
    } else {
        $hash = hash("sha256", $token);
        $stmt = $conn->prepare(
            "SELECT id FROM password_reset_tokens
             WHERE token_hash = ? AND used_at IS NULL AND expires_at > NOW()
             LIMIT 1"
        );
        $stmt->bind_param("s", $hash);
        $stmt->execute();
        $reset = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$reset) {
            $message = "This recovery link is invalid or expired.";
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            mysqli_begin_transaction($conn);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $password_hash, $reset["id"]);
            $updated = $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare(
                "UPDATE password_reset_tokens SET used_at = NOW()
                 WHERE token_hash = ?"
            );
            $stmt->bind_param("s", $hash);
            $updated = $updated && $stmt->execute();
            $stmt->close();

            if ($updated) {
                mysqli_commit($conn);
                $message = "Password changed. You can now log in.";
                $success = true;
            } else {
                mysqli_rollback($conn);
                $message = "Unable to change the password.";
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Reset Password | ImmuniCare</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-body">
<main class="login-form-container">
    <h1>Reset password</h1>
    <p><?php echo e($message); ?></p>
    <?php if (!$success && $token !== "" && $message === ""): ?>
        <form method="POST">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="token" value="<?php echo e($token); ?>">
            <label for="password">New password</label>
            <input id="password" type="password" name="password" required>
            <label for="confirm_password">Confirm password</label>
            <input id="confirm_password" type="password" name="confirm_password" required>
            <button type="submit">Save password</button>
        </form>
    <?php endif; ?>
    <a href="login.php">Back to login</a>
</main>
</body>
</html>
