<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/auth.php';

header('Content-Type: application/json; charset=utf-8');

$pdo = db();
$method = $_SERVER['REQUEST_METHOD'];

function json_input(): array {
    $raw = file_get_contents('php://input');
    if (!$raw) return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function respond(array $payload, int $status = 200): never {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function normalize_event(array $row): array {
    $status = $row['status'];
    $publicStatus = match ($status) {
        'finished' => 'past',
        'cancelled' => 'cancelled',
        default => $status,
    };
    $start = $row['start_date'] ?? null;
    $end = $row['end_date'] ?? null;
    $date = $start ? ($end && $end !== $start ? $start . ' تا ' . $end : $start) : null;
    $time = ($row['start_time'] || $row['end_time']) ? trim(($row['start_time'] ?? '') . ' - ' . ($row['end_time'] ?? '')) : null;
    return [
        'id' => (int)$row['id'],
        'title' => $row['title'],
        'category' => $row['category'],
        'status' => $publicStatus,
        'status_db' => $status,
        'date' => $date,
        'start_date' => $start,
        'end_date' => $end,
        'time' => $time,
        'start_time' => $row['start_time'],
        'end_time' => $row['end_time'],
        'duration' => $row['duration'],
        'location' => $row['location'],
        'address' => $row['address'],
        'organizer' => $row['organizer'],
        'image' => $row['image'] ?: 'images/event-placeholder.jpg',
        'description' => $row['description'],
        'longDescription' => $row['long_description'],
        'price' => $row['price'] === null ? 0 : (float)$row['price'],
        'capacity' => $row['capacity'] === null ? null : (int)$row['capacity'],
        'deadline' => $row['submission_deadline'],
        'submission_deadline' => $row['submission_deadline'],
        'participants' => isset($row['participants']) ? (int)$row['participants'] : 0,
        'papers' => isset($row['papers']) ? (int)$row['papers'] : 0,
        'presentations' => isset($row['presentations']) ? (int)$row['presentations'] : 0,
        'created_at' => $row['created_at'] ?? null,
        'updated_at' => $row['updated_at'] ?? null,
    ];
}

if ($method === 'GET') {
    $id = isset($_GET['id']) && $_GET['id'] !== '' ? (int)$_GET['id'] : null;
    $adminMode = isset($_GET['admin']) && $_GET['admin'] === '1';

    if ($adminMode) {
        require_role(['secretariat', 'admin']);
        $stmt = $pdo->query('SELECT * FROM events ORDER BY COALESCE(start_date, "9999-12-31") ASC, id ASC');
        $rows = $stmt->fetchAll();
        $events = [];
        foreach ($rows as $row) {
            $idv = (int)$row['id'];
            $p = $pdo->prepare('SELECT COUNT(*) FROM registrations WHERE event_id = ? AND status = "registered"');
            $p->execute([$idv]);
            $row['participants'] = (int)$p->fetchColumn();
            $p = $pdo->prepare('SELECT COUNT(*) FROM articles WHERE event_id = ?');
            $p->execute([$idv]);
            $row['papers'] = (int)$p->fetchColumn();
            $row['presentations'] = 0;
            $events[] = normalize_event($row);
        }
        respond(['ok' => true, 'events' => $events]);
    }

    if ($id !== null) {
        $stmt = $pdo->prepare('SELECT e.*, (SELECT COUNT(*) FROM registrations r WHERE r.event_id=e.id AND r.status="registered") AS participants, (SELECT COUNT(*) FROM articles a WHERE a.event_id=e.id) AS papers FROM events e WHERE e.id=? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) respond(['ok' => false, 'message' => 'همایش موردنظر پیدا نشد.'], 404);
        $scheduleStmt = $pdo->prepare('SELECT id,schedule_date,start_time,end_time,title,speaker,hall,type,sort_order FROM schedules WHERE event_id=? ORDER BY schedule_date,start_time,sort_order,id');
        $scheduleStmt->execute([$id]);
        $schedule = array_map(static function(array $s): array {
            return [
                'id'=>(int)$s['id'], 'date'=>$s['schedule_date'], 'startTime'=>$s['start_time'], 'endTime'=>$s['end_time'],
                'time'=>trim(($s['start_time'] ?? '') . ' - ' . ($s['end_time'] ?? '')), 'title'=>$s['title'], 'speaker'=>$s['speaker'], 'hall'=>$s['hall'], 'type'=>$s['type']
            ];
        }, $scheduleStmt->fetchAll());
        $event = normalize_event($row);
        $event['schedule'] = $schedule;
        $event['speakers'] = [];
        respond(['ok' => true, 'event' => $event]);
    }

    $stmt = $pdo->query('SELECT e.*, (SELECT COUNT(*) FROM registrations r WHERE r.event_id=e.id AND r.status="registered") AS participants, (SELECT COUNT(*) FROM articles a WHERE a.event_id=e.id) AS papers FROM events e WHERE e.status <> "draft" AND e.status <> "cancelled" ORDER BY COALESCE(e.start_date, "9999-12-31") ASC, e.id ASC');
    $rows = $stmt->fetchAll();
    respond(['ok' => true, 'events' => array_map('normalize_event', $rows)]);
}

require_role(['secretariat', 'admin']);

if ($method === 'POST') {
    $data = json_input();
    $title = trim((string)($data['title'] ?? ''));
    $status = (string)($data['status'] ?? 'draft');
    $allowed = ['draft','upcoming','ongoing','finished','cancelled'];
    if ($title === '' || !in_array($status, $allowed, true)) respond(['ok'=>false,'message'=>'عنوان و وضعیت همایش معتبر نیست.'],422);

    $stmt = $pdo->prepare('INSERT INTO events (title,category,status,start_date,end_date,start_time,end_time,duration,location,address,organizer,description,long_description,image,price,capacity,submission_deadline) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
    $stmt->execute([
        $title,
        trim((string)($data['category'] ?? '')) ?: null,
        $status,
        ($data['start_date'] ?? '') ?: null,
        ($data['end_date'] ?? '') ?: null,
        ($data['start_time'] ?? '') ?: null,
        ($data['end_time'] ?? '') ?: null,
        trim((string)($data['duration'] ?? '')) ?: null,
        trim((string)($data['location'] ?? '')) ?: null,
        trim((string)($data['address'] ?? '')) ?: null,
        trim((string)($data['organizer'] ?? '')) ?: null,
        trim((string)($data['description'] ?? '')) ?: null,
        trim((string)($data['long_description'] ?? '')) ?: null,
        trim((string)($data['image'] ?? '')) ?: null,
        is_numeric($data['price'] ?? null) ? (float)$data['price'] : 0,
        isset($data['capacity']) && $data['capacity'] !== '' ? (int)$data['capacity'] : null,
        ($data['submission_deadline'] ?? '') ?: null,
    ]);
    $id = (int)$pdo->lastInsertId();
    respond(['ok'=>true,'message'=>'همایش با موفقیت ایجاد شد.','id'=>$id],201);
}

if ($method === 'PATCH') {
    $data = json_input();
    $id = (int)($data['id'] ?? 0);
    if ($id < 1) respond(['ok'=>false,'message'=>'شناسه همایش معتبر نیست.'],422);
    $check = $pdo->prepare('SELECT id FROM events WHERE id=? LIMIT 1');
    $check->execute([$id]);
    if (!$check->fetch()) respond(['ok'=>false,'message'=>'همایش پیدا نشد.'],404);

    $allowed = ['title','category','status','start_date','end_date','start_time','end_time','duration','location','address','organizer','description','long_description','image','price','capacity','submission_deadline'];
    $set = [];
    $params = [];
    foreach ($allowed as $field) {
        if (array_key_exists($field, $data)) {
            if ($field === 'status' && !in_array((string)$data[$field], ['draft','upcoming','ongoing','finished','cancelled'], true)) respond(['ok'=>false,'message'=>'وضعیت نامعتبر است.'],422);
            $set[] = $field . ' = ?';
            $params[] = $data[$field] === '' ? null : $data[$field];
        }
    }
    if (!$set) respond(['ok'=>false,'message'=>'هیچ تغییری ارسال نشده است.'],422);
    $params[] = $id;
    $pdo->prepare('UPDATE events SET ' . implode(', ', $set) . ' WHERE id=?')->execute($params);
    respond(['ok'=>true,'message'=>'اطلاعات همایش به‌روزرسانی شد.']);
}

if ($method === 'DELETE') {
    $data = json_input();
    $id = (int)($data['id'] ?? 0);
    if ($id < 1) respond(['ok'=>false,'message'=>'شناسه همایش معتبر نیست.'],422);
    $check = $pdo->prepare('SELECT id,title FROM events WHERE id=? LIMIT 1');
    $check->execute([$id]);
    $event = $check->fetch();
    if (!$event) respond(['ok'=>false,'message'=>'همایش پیدا نشد.'],404);
    $count = $pdo->prepare('SELECT (SELECT COUNT(*) FROM articles WHERE event_id=?) + (SELECT COUNT(*) FROM registrations WHERE event_id=?) + (SELECT COUNT(*) FROM schedules WHERE event_id=?)');
    $count->execute([$id,$id,$id]);
    $linked = (int)$count->fetchColumn();
    if ($linked > 0) respond(['ok'=>false,'message'=>'این همایش به مقاله، ثبت‌نام یا برنامه وابسته است؛ به‌جای حذف، وضعیت آن را لغو کنید.'],409);
    $pdo->prepare('DELETE FROM events WHERE id=?')->execute([$id]);
    respond(['ok'=>true,'message'=>'همایش حذف شد.']);
}

respond(['ok'=>false,'message'=>'Method Not Allowed'],405);
