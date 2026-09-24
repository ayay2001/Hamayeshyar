<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../config/notifications.php';

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
function registration_row(array $r): array {
    return [
        'id'=>(int)$r['id'],
        'event_id'=>(int)$r['event_id'],
        'event_title'=>$r['event_title'] ?? null,
        'user_id'=>$r['user_id'] !== null ? (int)$r['user_id'] : null,
        'name'=>$r['name'], 'email'=>$r['email'], 'phone'=>$r['phone'],
        'organization'=>$r['organization'] ?? null, 'message'=>$r['message'] ?? null,
        'status'=>$r['status'], 'registered_at'=>$r['registered_at']
    ];
}

if ($method === 'GET') {
    if (isset($_GET['admin']) && $_GET['admin'] === '1') {
        require_role(['secretariat','admin']);
        $eventId = isset($_GET['event_id']) && $_GET['event_id'] !== '' ? (int)$_GET['event_id'] : null;
        $status = trim((string)($_GET['status'] ?? ''));
        $sql = 'SELECT r.*, e.title AS event_title FROM registrations r JOIN events e ON e.id=r.event_id WHERE 1=1';
        $params=[];
        if ($eventId) { $sql .= ' AND r.event_id=?'; $params[]=$eventId; }
        if (in_array($status,['registered','cancelled','attended'],true)) { $sql .= ' AND r.status=?'; $params[]=$status; }
        $sql .= ' ORDER BY r.registered_at DESC, r.id DESC';
        $st=$pdo->prepare($sql); $st->execute($params);
        $rows=array_map('registration_row',$st->fetchAll());
        respond(['ok'=>true,'registrations'=>$rows]);
    }

    if (isset($_GET['my']) && $_GET['my'] === '1') {
        $user=require_auth();
        $st=$pdo->prepare('SELECT r.*, e.title AS event_title FROM registrations r JOIN events e ON e.id=r.event_id WHERE r.user_id=? ORDER BY r.registered_at DESC, r.id DESC');
        $st->execute([(int)$user['id']]);
        respond(['ok'=>true,'registrations'=>array_map('registration_row',$st->fetchAll())]);
    }

    $eventId = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;
    if ($eventId < 1) respond(['ok'=>false,'message'=>'شناسه همایش معتبر نیست.'],422);

    $st=$pdo->prepare('SELECT e.id,e.title,e.status,e.capacity, (SELECT COUNT(*) FROM registrations r WHERE r.event_id=e.id AND r.status="registered") AS participants FROM events e WHERE e.id=? LIMIT 1');
    $st->execute([$eventId]); $event=$st->fetch();
    if (!$event) respond(['ok'=>false,'message'=>'همایش پیدا نشد.'],404);
    $my=null;
    if (!empty($_SESSION['user']['id'])) {
        $st=$pdo->prepare('SELECT r.*, e.title AS event_title FROM registrations r JOIN events e ON e.id=r.event_id WHERE r.event_id=? AND r.user_id=? LIMIT 1');
        $st->execute([$eventId,(int)$_SESSION['user']['id']]);
        $row=$st->fetch(); if ($row) $my=registration_row($row);
    }
    $remaining=$event['capacity'] === null ? null : max(0,(int)$event['capacity']-(int)$event['participants']);
    respond(['ok'=>true,'event'=>['id'=>(int)$event['id'],'title'=>$event['title'],'status'=>$event['status'],'capacity'=>$event['capacity']===null?null:(int)$event['capacity'],'participants'=>(int)$event['participants'],'remaining'=>$remaining],'registration'=>$my]);
}

if ($method === 'POST') {
    $data=json_input();
    $eventId=(int)($data['event_id']??0);
    $name=trim((string)($data['name']??''));
    $email=strtolower(trim((string)($data['email']??'')));
    $phone=trim((string)($data['phone']??''));
    $organization=trim((string)($data['organization']??''));
    $message=trim((string)($data['message']??''));
    if ($eventId<1 || $name==='' || !filter_var($email,FILTER_VALIDATE_EMAIL)) respond(['ok'=>false,'message'=>'اطلاعات ثبت‌نام کامل یا معتبر نیست.'],422);
    if (mb_strlen($name)>150 || mb_strlen($phone)>30 || mb_strlen($organization)>255 || mb_strlen($message)>1000) respond(['ok'=>false,'message'=>'یکی از فیلدها بیش از حد مجاز است.'],422);

    $st=$pdo->prepare('SELECT id,title,status,capacity,(SELECT COUNT(*) FROM registrations rr WHERE rr.event_id=e.id AND rr.status="registered") AS participants FROM events e WHERE e.id=? LIMIT 1');
    $st->execute([$eventId]); $event=$st->fetch();
    if (!$event) respond(['ok'=>false,'message'=>'همایش پیدا نشد.'],404);
    if (!in_array($event['status'],['upcoming','ongoing'],true)) respond(['ok'=>false,'message'=>'ثبت‌نام در این همایش در حال حاضر امکان‌پذیر نیست.'],409);
    if ($event['capacity'] !== null && (int)$event['participants'] >= (int)$event['capacity']) respond(['ok'=>false,'message'=>'ظرفیت این همایش تکمیل شده است.'],409);

    $sessionUser=$_SESSION['user']??null;
    $userId=$sessionUser && !empty($sessionUser['id']) ? (int)$sessionUser['id'] : null;

    $st=$pdo->prepare('SELECT * FROM registrations WHERE event_id=? AND email=? LIMIT 1');
    $st->execute([$eventId,$email]); $existing=$st->fetch();
    if ($existing) {
        if ($existing['status']==='registered') respond(['ok'=>false,'message'=>'این ایمیل قبلاً در این همایش ثبت‌نام کرده است.'],409);
        $st=$pdo->prepare('UPDATE registrations SET user_id=?,name=?,phone=?,organization=?,message=?,status="registered",registered_at=CURRENT_TIMESTAMP WHERE id=?');
        $st->execute([$userId,$name,$phone?:null,$organization?:null,$message?:null,(int)$existing['id']]);
        respond(['ok'=>true,'message'=>'ثبت‌نام مجدد شما با موفقیت انجام شد.','id'=>(int)$existing['id'],'status'=>'registered']);
    }

    $st=$pdo->prepare('INSERT INTO registrations (event_id,user_id,name,email,phone,organization,message,status) VALUES (?,?,?,?,?,?,?,"registered")');
    $st->execute([$eventId,$userId,$name,$email,$phone?:null,$organization?:null,$message?:null]);
    $registrationId=(int)$pdo->lastInsertId();
    notify_roles($pdo,['secretariat','admin'],'ثبت‌نام جدید در همایش','کاربر «'.$name.'» در همایش «'.$event['title'].'» ثبت‌نام کرد.','registration');
    if ($userId) notify_user($pdo,$userId,'ثبت‌نام شما تأیید شد','ثبت‌نام شما در همایش «'.$event['title'].'» با موفقیت انجام شد.','registration');
    respond(['ok'=>true,'message'=>'ثبت‌نام با موفقیت انجام شد.','id'=>$registrationId,'status'=>'registered'],201);
}

if ($method === 'PATCH') {
    $data=json_input();
    $id=(int)($data['id']??0);
    $targetStatus=(string)($data['status']??'');
    if ($id<1 || !in_array($targetStatus,['registered','cancelled','attended'],true)) respond(['ok'=>false,'message'=>'پارامترهای تغییر وضعیت معتبر نیستند.'],422);

    $user=$_SESSION['user']??null;
    $isAdmin=$user && in_array($user['role'],['secretariat','admin'],true);
    if (!$user) respond(['ok'=>false,'message'=>'برای تغییر وضعیت ثبت‌نام باید وارد حساب شوید.'],401);

    $st=$pdo->prepare('SELECT * FROM registrations WHERE id=? LIMIT 1'); $st->execute([$id]); $row=$st->fetch();
    if (!$row) respond(['ok'=>false,'message'=>'ثبت‌نام پیدا نشد.'],404);
    if (!$isAdmin && ((int)($row['user_id']??0)!==(int)$user['id'] || $targetStatus!=='cancelled')) respond(['ok'=>false,'message'=>'دسترسی تغییر این ثبت‌نام را ندارید.'],403);

    if ($targetStatus==='registered' && $row['status']!=='registered') {
        $st=$pdo->prepare('SELECT capacity,(SELECT COUNT(*) FROM registrations rr WHERE rr.event_id=e.id AND rr.status="registered") AS participants,status FROM events e WHERE e.id=?');
        $st->execute([(int)$row['event_id']]); $event=$st->fetch();
        if (!$event || !in_array($event['status'],['upcoming','ongoing'],true)) respond(['ok'=>false,'message'=>'این همایش در حال حاضر برای ثبت‌نام فعال نیست.'],409);
        if ($event['capacity'] !== null && (int)$event['participants'] >= (int)$event['capacity']) respond(['ok'=>false,'message'=>'ظرفیت همایش تکمیل شده است.'],409);
    }

    $st=$pdo->prepare('UPDATE registrations SET status=? WHERE id=?'); $st->execute([$targetStatus,$id]);
    respond(['ok'=>true,'message'=>$targetStatus==='cancelled'?'ثبت‌نام لغو شد.':($targetStatus==='attended'?'وضعیت حضور ثبت شد.':'ثبت‌نام فعال شد.'),'status'=>$targetStatus]);
}

respond(['ok'=>false,'message'=>'Method Not Allowed'],405);
