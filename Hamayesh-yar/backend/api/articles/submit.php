<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/notifications.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Method Not Allowed'], JSON_UNESCAPED_UNICODE);
    exit;
}

$user = require_role(['author']);
$title = trim((string)($_POST['title'] ?? ''));
$abstract = trim((string)($_POST['abstract'] ?? ''));
$keywords = trim((string)($_POST['keywords'] ?? ''));
$eventId = isset($_POST['event_id']) && $_POST['event_id'] !== '' ? (int)$_POST['event_id'] : null;

$words = $abstract === '' ? [] : preg_split('/\s+/u', $abstract, -1, PREG_SPLIT_NO_EMPTY);

if ($title === '' || $abstract === '' || count($words) > 300) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'عنوان یا چکیده معتبر نیست. چکیده باید حداکثر ۳۰۰ کلمه باشد.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($_FILES['file']) || !is_uploaded_file($_FILES['file']['tmp_name'])) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'فایل مقاله ارسال نشده است.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$file = $_FILES['file'];
if ((int)$file['error'] !== UPLOAD_ERR_OK) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'آپلود فایل با خطا مواجه شد.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ((int)$file['size'] > 5 * 1024 * 1024) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'حجم فایل نباید بیشتر از ۵ مگابایت باشد.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$extension = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
if ($extension !== 'pdf') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'فقط فایل PDF مجاز است.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($file['tmp_name']);
if ($mime !== 'application/pdf') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'نوع واقعی فایل PDF معتبر نیست.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($eventId !== null) {
    $stmt = db()->prepare('SELECT id FROM events WHERE id = ? LIMIT 1');
    $stmt->execute([$eventId]);
    if (!$stmt->fetch()) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'message' => 'همایش انتخاب‌شده وجود ندارد.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

$uploadDir = __DIR__ . '/../../uploads/papers';
if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'پوشه آپلود قابل ایجاد نیست.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$uniqueName = bin2hex(random_bytes(16)) . '.pdf';
$targetPath = $uploadDir . DIRECTORY_SEPARATOR . $uniqueName;
$relativePath = 'backend/uploads/papers/' . $uniqueName;

if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'ذخیره فایل مقاله انجام نشد.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $stmt = db()->prepare(
        'INSERT INTO articles (event_id, author_id, title, abstract, keywords, file_path, status)
         VALUES (?, ?, ?, ?, ?, ?, \'pending\')'
    );
    $stmt->execute([$eventId, (int)$user['id'], $title, $abstract, $keywords ?: null, $relativePath]);
    $articleId = (int)db()->lastInsertId();
    $eventTitle = null;
    if ($eventId !== null) {
        $eventStmt = db()->prepare('SELECT title FROM events WHERE id=? LIMIT 1');
        $eventStmt->execute([$eventId]);
        $eventTitle = $eventStmt->fetchColumn() ?: null;
    }
    $notifyTitle = 'مقاله جدید شما ثبت شد';
    $notifyMessage = 'مقاله «' . $title . '» با موفقیت ثبت شد و در انتظار بررسی دبیرخانه است.';
    if ($eventTitle) { $notifyMessage .= ' همایش: ' . $eventTitle . '.'; }
    notify_user(db(), (int)$user['id'], $notifyTitle, $notifyMessage, 'article');
    notify_roles(db(), ['secretariat','admin'], 'مقاله جدید دریافت شد', 'مقاله «' . $title . '» از طرف ' . $user['name'] . ' ثبت شده و در انتظار بررسی است.', 'article');
} catch (Throwable $e) {
    @unlink($targetPath);
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'ثبت مقاله در دیتابیس انجام نشد.'], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode([
    'ok' => true,
    'message' => 'مقاله با موفقیت ارسال شد و در انتظار بررسی دبیرخانه است.',
    'article' => [
        'id' => $articleId,
        'title' => $title,
        'abstract' => $abstract,
        'keywords' => $keywords,
        'status' => 'pending',
        'final_decision' => null,
        'file_path' => $relativePath,
        'submitted_at' => date('Y-m-d H:i:s')
    ]
], JSON_UNESCAPED_UNICODE);
