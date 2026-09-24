<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth.php';

header('Content-Type: application/json; charset=utf-8');

$user = require_role(['secretariat', 'admin']);
$pdo = db();
$method = $_SERVER['REQUEST_METHOD'];

function json_input(): array
{
    $raw = file_get_contents('php://input');
    if (!$raw) return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function respond(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($method === 'GET') {
    $stmt = $pdo->query("SELECT id,name,email,phone,expertise,organization,bio,avatar,status,created_at FROM users WHERE role='reviewer' ORDER BY name ASC, id ASC");
    respond(['ok' => true, 'reviewers' => $stmt->fetchAll()]);
}

if ($method === 'POST') {
    $data = json_input();
    $name = trim((string)($data['name'] ?? ''));
    $email = strtolower(trim((string)($data['email'] ?? '')));
    $password = (string)($data['password'] ?? '');
    $expertise = trim((string)($data['expertise'] ?? ''));
    $organization = trim((string)($data['organization'] ?? ''));
    $phone = trim((string)($data['phone'] ?? ''));
    $bio = trim((string)($data['bio'] ?? ''));

    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
        respond(['ok' => false, 'message' => 'نام، ایمیل معتبر و رمز عبور حداقل ۸ کاراکتری الزامی است.'], 422);
    }

    $check = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $check->execute([$email]);
    if ($check->fetch()) {
        respond(['ok' => false, 'message' => 'این ایمیل قبلاً در سامانه ثبت شده است.'], 409);
    }

    $stmt = $pdo->prepare("INSERT INTO users (name,email,phone,password_hash,role,expertise,organization,bio,status) VALUES (?,?,?,?, 'reviewer',?,?,?,'active')");
    $stmt->execute([
        $name,
        $email,
        $phone !== '' ? $phone : null,
        password_hash($password, PASSWORD_DEFAULT),
        $expertise !== '' ? $expertise : null,
        $organization !== '' ? $organization : null,
        $bio !== '' ? $bio : null,
    ]);

    $id = (int)$pdo->lastInsertId();
    $get = $pdo->prepare("SELECT id,name,email,phone,expertise,organization,bio,avatar,status,created_at FROM users WHERE id=?");
    $get->execute([$id]);

    respond(['ok' => true, 'message' => 'داور با موفقیت ایجاد شد.', 'reviewer' => $get->fetch()], 201);
}

if ($method === 'PATCH') {
    $data = json_input();
    $id = (int)($data['id'] ?? 0);
    $status = (string)($data['status'] ?? '');

    if ($id < 1 || !in_array($status, ['active', 'inactive', 'blocked'], true)) {
        respond(['ok' => false, 'message' => 'شناسه یا وضعیت داور معتبر نیست.'], 422);
    }

    $check = $pdo->prepare("SELECT id FROM users WHERE id=? AND role='reviewer' LIMIT 1");
    $check->execute([$id]);
    if (!$check->fetch()) respond(['ok' => false, 'message' => 'داور موردنظر پیدا نشد.'], 404);

    $stmt = $pdo->prepare('UPDATE users SET status=? WHERE id=?');
    $stmt->execute([$status, $id]);

    respond(['ok' => true, 'message' => 'وضعیت داور به‌روزرسانی شد.']);
}

respond(['ok' => false, 'message' => 'Method Not Allowed'], 405);
