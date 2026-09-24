<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Method Not Allowed'], JSON_UNESCAPED_UNICODE);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$email = strtolower(trim((string)($data['email'] ?? '')));
$password = (string)($data['password'] ?? '');

$stmt = db()->prepare('SELECT id, name, email, phone, password_hash, role, expertise, organization, bio, avatar, status FROM users WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || $user['status'] !== 'active' || !password_verify($password, $user['password_hash'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'ایمیل یا رمز عبور اشتباه است.'], JSON_UNESCAPED_UNICODE);
    exit;
}

session_regenerate_id(true);
unset($user['password_hash']);
$_SESSION['user'] = $user;

echo json_encode(['ok' => true, 'user' => $user], JSON_UNESCAPED_UNICODE);
