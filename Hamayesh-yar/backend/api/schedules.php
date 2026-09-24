<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/auth.php';

header('Content-Type: application/json; charset=utf-8');
$pdo = db();
$method = $_SERVER['REQUEST_METHOD'];

function input_json(): array {
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}
function out(bool $ok, string $message = '', array $extra = [], int $code = 200): never {
    http_response_code($code);
    echo json_encode(array_merge(['ok'=>$ok,'message'=>$message], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}
function row_json(array $r): array {
    return [
        'id'=>(int)$r['id'],
        'event_id'=>(int)$r['event_id'],
        'event_title'=>$r['event_title'] ?? null,
        'date'=>$r['schedule_date'],
        'startTime'=>$r['start_time'],
        'endTime'=>$r['end_time'],
        'time'=>trim(($r['start_time'] ?? '') . ' - ' . ($r['end_time'] ?? '')),
        'title'=>$r['title'],
        'speaker'=>$r['speaker'],
        'hall'=>$r['hall'],
        'type'=>$r['type'],
        'description'=>$r['description'] ?? null,
        'sort_order'=>(int)$r['sort_order'],
    ];
}

if ($method === 'GET') {
    $admin = ($_GET['admin'] ?? '') === '1';
    if ($admin) require_role(['secretariat','admin']);

    $eventId = isset($_GET['event_id']) && $_GET['event_id'] !== '' ? (int)$_GET['event_id'] : 0;
    if ($eventId < 1) {
        $eventId = (int)($pdo->query("SELECT id FROM events WHERE status <> 'cancelled' ORDER BY COALESCE(start_date,'9999-12-31'), id LIMIT 1")->fetchColumn() ?: 0);
    }

    $sql = 'SELECT s.id,s.event_id,s.schedule_date,s.start_time,s.end_time,s.title,s.speaker,s.hall,s.type,s.description,s.sort_order,e.title AS event_title FROM schedules s JOIN events e ON e.id=s.event_id';
    $params = [];
    if ($eventId > 0) { $sql .= ' WHERE s.event_id=?'; $params[] = $eventId; }
    $sql .= ' ORDER BY s.schedule_date,s.start_time,s.sort_order,s.id';
    $stmt = $pdo->prepare($sql); $stmt->execute($params);
    $items = array_map('row_json', $stmt->fetchAll());

    $event = null;
    if ($eventId > 0) {
        $ev = $pdo->prepare('SELECT id,title,start_date,end_date,location,start_time,end_time FROM events WHERE id=? LIMIT 1');
        $ev->execute([$eventId]); $event = $ev->fetch() ?: null;
    }
    out(true,'',['event'=>$event,'schedule'=>$items]);
}

require_role(['secretariat','admin']);

if ($method === 'POST') {
    $d = input_json();
    $eventId=(int)($d['event_id']??0);
    $title=trim((string)($d['title']??''));
    $date=trim((string)($d['date']??$d['schedule_date']??''));
    $start=trim((string)($d['startTime']??$d['start_time']??''));
    $end=trim((string)($d['endTime']??$d['end_time']??''));
    if($eventId<1||$title==='') out(false,'همایش و عنوان برنامه الزامی است.',[],422);
    $ev=$pdo->prepare('SELECT id FROM events WHERE id=? LIMIT 1');$ev->execute([$eventId]);if(!$ev->fetch())out(false,'همایش پیدا نشد.',[],404);
    if($date!=='' && !preg_match('/^\d{4}-\d{2}-\d{2}$/',$date)) out(false,'تاریخ نامعتبر است.',[],422);
    if($start!=='' && !preg_match('/^\d{2}:\d{2}(:\d{2})?$/',$start)) out(false,'ساعت شروع نامعتبر است.',[],422);
    if($end!=='' && !preg_match('/^\d{2}:\d{2}(:\d{2})?$/',$end)) out(false,'ساعت پایان نامعتبر است.',[],422);
    $stmt=$pdo->prepare('INSERT INTO schedules(event_id,schedule_date,start_time,end_time,title,speaker,hall,type,description,sort_order) VALUES(?,?,?,?,?,?,?,?,?,?)');
    $stmt->execute([$eventId,$date?:null,$start?:null,$end?:null,$title,trim((string)($d['speaker']??''))?:null,trim((string)($d['hall']??''))?:null,trim((string)($d['type']??''))?:null,trim((string)($d['description']??''))?:null,(int)($d['sort_order']??0)]);
    out(true,'برنامه با موفقیت اضافه شد.',['id'=>(int)$pdo->lastInsertId()],201);
}

if ($method === 'PATCH') {
    $d=input_json();$id=(int)($d['id']??0);if($id<1)out(false,'شناسه برنامه نامعتبر است.',[],422);
    $allowed=['event_id','date','schedule_date','startTime','start_time','endTime','end_time','title','speaker','hall','type','description','sort_order'];
    $map=['date'=>'schedule_date','startTime'=>'start_time','endTime'=>'end_time'];
    $set=[];$params=[];
    foreach($allowed as $field){
        if(!array_key_exists($field,$d))continue;
        $column=$map[$field]??$field;
        $set[]="$column=?";
        $params[]=$d[$field]===''?null:$d[$field];
    }
    if(!$set)out(false,'تغییری ارسال نشده است.',[],422);
    $params[]=$id;$stmt=$pdo->prepare('UPDATE schedules SET '.implode(',',$set).' WHERE id=?');$stmt->execute($params);
    if(!$stmt->rowCount()){
        $check=$pdo->prepare('SELECT id FROM schedules WHERE id=?');$check->execute([$id]);if(!$check->fetch())out(false,'برنامه پیدا نشد.',[],404);
    }
    out(true,'برنامه به‌روزرسانی شد.');
}

if ($method === 'DELETE') {
    $d=input_json();$id=(int)($d['id']??0);if($id<1)out(false,'شناسه برنامه نامعتبر است.',[],422);
    $stmt=$pdo->prepare('DELETE FROM schedules WHERE id=?');$stmt->execute([$id]);
    if($stmt->rowCount()===0)out(false,'برنامه پیدا نشد.',[],404);
    out(true,'برنامه حذف شد.');
}

out(false,'Method Not Allowed',[],405);
