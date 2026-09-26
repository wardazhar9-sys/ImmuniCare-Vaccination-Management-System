<?php

require_once __DIR__ . '/app.php';

function api_response(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function api_input(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') {
        return [];
    }

    $data = json_decode($raw, true);
    if (!is_array($data)) {
        api_response(['error' => 'Invalid JSON body.'], 400);
    }

    return $data;
}

function api_user(mysqli $conn): array
{
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (!preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
        api_response(['error' => 'Bearer token required.'], 401);
    }

    $hash = hash('sha256', trim($matches[1]));
    $stmt = $conn->prepare(
        "SELECT u.id, u.name, u.email, u.role, u.status
         FROM api_tokens t JOIN users u ON u.id = t.user_id
         WHERE t.token_hash = ? AND t.revoked_at IS NULL
           AND (t.expires_at IS NULL OR t.expires_at > NOW())
         LIMIT 1"
    );
    $stmt->bind_param('s', $hash);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user || $user['status'] !== 'Active') {
        api_response(['error' => 'Invalid or inactive token.'], 401);
    }

    return $user;
}

function api_method(string $method): void
{
    if ($_SERVER['REQUEST_METHOD'] !== $method) {
        header('Allow: ' . $method);
        api_response(['error' => 'Method not allowed.'], 405);
    }
}
