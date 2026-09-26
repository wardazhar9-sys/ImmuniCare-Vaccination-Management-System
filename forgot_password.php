<?php

require_once "includes/app.php";

$message = "If the account exists, recovery instructions have been queued.";
$recovery_link = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verify_csrf();
    $email = post_string("email", 150);
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND status = 'Active'");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($user) {
            $plain = bin2hex(random_bytes(32));
            $hash = hash("sha256", $plain);
            $stmt = $conn->prepare(
                "INSERT INTO password_reset_tokens (user_id, token_hash, expires_at)
                 VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 30 MINUTE))"
            );
            $stmt->bind_param("is", $user["id"], $hash);
            $stmt->execute();
            $stmt->close();

            $payload = json_encode([
                "email" => $email,
                "url" => "reset_password.php?token=" . $plain
            ]);
            $channel = "email";
            $event = "password_reset";
            $outbox = $conn->prepare(
                "INSERT INTO notification_outbox (user_id, channel, event_type, payload)
                 VALUES (?, ?, ?, ?)"
            );
            $outbox->bind_param("isss", $user["id"], $channel, $event, $payload);
            $outbox->execute();
            $outbox->close();

            if ((getenv("APP_ENV") ?: "production") !== "production") {
                $recovery_link = "reset_password.php?token=" . $plain;
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
    <title>Forgot Password | ImmuniCare</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/form-validation.js" defer></script>
</head>
<body class="login-body">
<main class="auth-simple-page">
    <h1>Reset your password</h1>
    <p><?php echo e($message); ?></p>
    <?php if ($recovery_link !== ""): ?>
        <div class="local-recovery-preview">
            <strong>Local recovery preview</strong>
            <p>Email delivery is disabled locally. Open this link to complete the reset:</p>
            <a href="<?php echo e($recovery_link); ?>"><?php echo e($recovery_link); ?></a>
        </div>
    <?php endif; ?>
    <form method="POST" class="auth-simple-form">
        <?php echo csrf_field(); ?>
        <label for="email">Email address</label>
        <input id="email" type="email" name="email" maxlength="150" autocomplete="email" required>
        <button type="submit">Send recovery link</button>
    </form>
    <a class="auth-back-link" href="login.php">Back to login</a>
</main>
</body>
</html>
