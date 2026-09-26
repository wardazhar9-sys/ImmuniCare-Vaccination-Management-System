<?php

require_once "includes/app.php";

$token = (string)($_GET["token"] ?? "");
$message = "This verification link is invalid or expired.";

if (strlen($token) === 64) {
    $hash = hash("sha256", $token);
    $stmt = $conn->prepare(
        "SELECT user_id FROM email_verification_tokens
         WHERE token_hash = ? AND used_at IS NULL AND expires_at > NOW()
         LIMIT 1"
    );
    $stmt->bind_param("s", $hash);
    $stmt->execute();
    $verification = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($verification) {
        mysqli_begin_transaction($conn);
        $stmt = $conn->prepare("UPDATE users SET email_verified_at = NOW() WHERE id = ?");
        $stmt->bind_param("i", $verification["user_id"]);
        $ok = $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare(
            "UPDATE email_verification_tokens SET used_at = NOW() WHERE token_hash = ?"
        );
        $stmt->bind_param("s", $hash);
        $ok = $ok && $stmt->execute();
        $stmt->close();

        if ($ok) {
            mysqli_commit($conn);
            $message = "Email verified. You can now log in.";
        } else {
            mysqli_rollback($conn);
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Verify Email | ImmuniCare</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-body">
<main class="auth-simple-page">
    <h1>Email verification</h1>
    <p><?php echo e($message); ?></p>
    <a class="auth-back-link" href="login.php">Go to login</a>
</main>
</body>
</html>
