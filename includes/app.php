<?php

require_once __DIR__ . '/../config/db.php';

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_cookies', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.gc_maxlifetime', '28800');
    session_name('immunicare_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'
    ]);
    session_start();
}

date_default_timezone_set(getenv('APP_TIMEZONE') ?: 'Asia/Karachi');

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function redirect_to(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = $_POST['_csrf'] ?? '';

    if (!hash_equals($_SESSION['_csrf'] ?? '', $token)) {
        http_response_code(419);
        exit('The form has expired. Please try again.');
    }
}

function flash_set(string $message, string $type = 'success'): void
{
    $_SESSION['_flash'] = ['message' => $message, 'type' => $type];
}

function flash_get(): array
{
    $flash = $_SESSION['_flash'] ?? ['message' => '', 'type' => ''];
    unset($_SESSION['_flash']);
    return $flash;
}

function current_user(mysqli $conn): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    $stmt = $conn->prepare(
        'SELECT id, name, email, role, status FROM users WHERE id = ? LIMIT 1'
    );
    if (!$stmt) {
        error_log('Current user lookup failed: ' . $conn->error);
        return null;
    }
    $id = (int)$_SESSION['user_id'];
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();

    if (!$user || ($user['status'] ?? 'Active') !== 'Active') {
        return null;
    }

    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['name'] = $user['name'];
    $_SESSION['role'] = $user['role'];
    return $user;
}

function require_role(mysqli $conn, string $role, string $loginPath = '../login.php'): array
{
    $user = current_user($conn);

    if (!$user || $user['role'] !== $role) {
        redirect_to($loginPath);
    }

    return $user;
}

function post_string(string $key, int $max = 255): string
{
    $value = trim((string)($_POST[$key] ?? ''));
    return mb_substr($value, 0, $max);
}

function post_int(string $key): int
{
    $value = filter_input(INPUT_POST, $key, FILTER_VALIDATE_INT);
    return $value === false || $value === null ? 0 : (int)$value;
}

function valid_date(string $value): bool
{
    $date = DateTime::createFromFormat('!Y-m-d', $value);
    return $date !== false && $date->format('Y-m-d') === $value;
}

function valid_time(string $value): bool
{
    $time = DateTime::createFromFormat('!H:i', $value);
    return $time !== false && $time->format('H:i') === $value;
}

function notify_user(
    mysqli $conn,
    int $userId,
    string $title,
    string $message,
    string $type,
    string $link = ''
): bool {
    if (getenv('TEST_FAIL_NOTIFICATIONS') === '1') {
        return false;
    }

    $stmt = $conn->prepare(
        'INSERT INTO notifications (user_id, title, message, type, link_url)
         VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->bind_param('issss', $userId, $title, $message, $type, $link);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

function audit(
    mysqli $conn,
    ?int $actorId,
    string $action,
    string $entityType,
    ?int $entityId,
    array $details = []
): void {
    $json = $details ? json_encode($details, JSON_UNESCAPED_UNICODE) : null;
    $stmt = $conn->prepare(
        'INSERT INTO audit_logs
         (actor_user_id, action, entity_type, entity_id, details)
         VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->bind_param('issis', $actorId, $action, $entityType, $entityId, $json);
    $stmt->execute();
    $stmt->close();
}

function format_date_value(?string $value): string
{
    return $value ? date('d M Y', strtotime($value)) : '—';
}

function format_time_value(?string $value): string
{
    return $value ? date('h:i A', strtotime($value)) : '—';
}
