<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Method Not Allowed'], JSON_UNESCAPED_UNICODE);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$name = trim((string)($data['name'] ?? ''));
$email = strtolower(trim((string)($data['email'] ?? '')));
$phone = trim((string)($data['phone'] ?? ''));
$role = (string)($data['role'] ?? 'author');
$password = (string)($data['password'] ?? '');

if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'اطلاعات ثبت‌نام معتبر نیست.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ثبت‌نام عمومی فقط نویسنده است؛ ایجاد داور از پنل دبیرخانه انجام می‌شود.
if (!in_array($role, ['author'], true)) {
    $role = 'author';
}

$pdo = db();
$stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
if ($stmt->fetch()) {
    http_response_code(409);
    echo json_encode(['ok' => false, 'message' => 'این ایمیل قبلاً ثبت شده است.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt = $pdo->prepare('INSERT INTO users (name, email, phone, password_hash, role) VALUES (?, ?, ?, ?, ?)');
$stmt->execute([$name, $email, $phone ?: null, password_hash($password, PASSWORD_DEFAULT), $role]);

http_response_code(201);
echo json_encode(['ok' => true, 'message' => 'ثبت‌نام با موفقیت انجام شد.'], JSON_UNESCAPED_UNICODE);
