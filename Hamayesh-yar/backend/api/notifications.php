<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/auth.php';
header('Content-Type: application/json; charset=utf-8');

$user = require_auth();
$pdo = db();
$method = $_SERVER['REQUEST_METHOD'];

function json_input(): array {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw ?: '', true);
    return is_array($data) ? $data : $_POST;
}
function respond(array $data, int $status = 200): never {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
function notification_row(array $row): array {
    return [
        'id' => (int)$row['id'],
        'title' => $row['title'],
        'message' => $row['message'],
        'type' => $row['type'] ?: 'info',
        'isRead' => (bool)$row['is_read'],
        'createdAt' => $row['created_at'],
    ];
}

if ($method === 'GET') {
    $limit = min(50, max(1, (int)($_GET['limit'] ?? 20)));
    $onlyUnread = isset($_GET['unread']) && $_GET['unread'] === '1';
    $where = $onlyUnread ? ' AND is_read=0' : '';
    $stmt = $pdo->prepare("SELECT id,title,message,type,is_read,created_at FROM notifications WHERE user_id=?{$where} ORDER BY created_at DESC,id DESC LIMIT {$limit}");
    $stmt->execute([(int)$user['id']]);
    $count = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0');
    $count->execute([(int)$user['id']]);
    respond(['ok'=>true,'notifications'=>array_map('notification_row',$stmt->fetchAll()),'unreadCount'=>(int)$count->fetchColumn()]);
}

if ($method === 'PATCH') {
    $data = json_input();
    $action = (string)($data['action'] ?? 'read');
    if ($action === 'all') {
        $stmt = $pdo->prepare('UPDATE notifications SET is_read=1 WHERE user_id=? AND is_read=0');
        $stmt->execute([(int)$user['id']]);
        respond(['ok'=>true,'message'=>'همه اعلان‌ها خوانده شدند.']);
    }
    $id = (int)($data['id'] ?? 0);
    if ($id < 1) respond(['ok'=>false,'message'=>'شناسه اعلان نامعتبر است.'],422);
    $stmt = $pdo->prepare('UPDATE notifications SET is_read=1 WHERE id=? AND user_id=?');
    $stmt->execute([$id,(int)$user['id']]);
    respond(['ok'=>true,'message'=>'اعلان خوانده شد.']);
}

respond(['ok'=>false,'message'=>'Method Not Allowed'],405);
