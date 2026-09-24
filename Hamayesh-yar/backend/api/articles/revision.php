<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Method Not Allowed'], JSON_UNESCAPED_UNICODE);
    exit;
}

$user = require_role(['author']);
$articleId = (int)($_POST['article_id'] ?? 0);
if ($articleId < 1 || !isset($_FILES['file']) || !is_uploaded_file($_FILES['file']['tmp_name'])) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'اطلاعات اصلاحیه کامل نیست.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt = db()->prepare('SELECT id, status, file_path FROM articles WHERE id = ? AND author_id = ? LIMIT 1');
$stmt->execute([$articleId, (int)$user['id']]);
$article = $stmt->fetch();
if (!$article) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'message' => 'مقاله یافت نشد یا دسترسی ندارید.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!in_array($article['status'], ['revision', 'rejected'], true)) {
    http_response_code(409);
    echo json_encode(['ok' => false, 'message' => 'در وضعیت فعلی امکان ارسال اصلاحیه وجود ندارد.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$file = $_FILES['file'];
if ((int)$file['error'] !== UPLOAD_ERR_OK || (int)$file['size'] > 5 * 1024 * 1024) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'فایل نامعتبر یا بزرگ‌تر از ۵ مگابایت است.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$extension = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
$mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
if ($extension !== 'pdf' || $mime !== 'application/pdf') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'فقط فایل PDF معتبر مجاز است.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$uploadDir = __DIR__ . '/../../uploads/papers';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}
$newName = bin2hex(random_bytes(16)) . '.pdf';
$targetPath = $uploadDir . DIRECTORY_SEPARATOR . $newName;
$relativePath = 'backend/uploads/papers/' . $newName;

if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'ذخیره اصلاحیه انجام نشد.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $stmt = db()->prepare("UPDATE articles SET revision_file_path = ?, status = 'pending', final_decision = NULL, revision_submitted_at = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->execute([$relativePath, $articleId]);
} catch (Throwable $e) {
    @unlink($targetPath);
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'ثبت اصلاحیه انجام نشد.'], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode(['ok' => true, 'message' => 'نسخه اصلاح‌شده با موفقیت ارسال شد.'], JSON_UNESCAPED_UNICODE);
