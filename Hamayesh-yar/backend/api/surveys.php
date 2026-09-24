<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/auth.php';
header('Content-Type: application/json; charset=utf-8');

function json_input(): array {
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}
function respond(bool $ok, string $message = '', array $extra = [], int $code = 200): never {
    http_response_code($code);
    echo json_encode(array_merge(['ok'=>$ok,'message'=>$message], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$pdo = db();

if ($method === 'GET') {
    $action = $_GET['action'] ?? 'active';
    if ($action === 'stats') {
        require_role(['secretariat','admin']);
        $surveyId = isset($_GET['survey_id']) && $_GET['survey_id'] !== '' ? (int)$_GET['survey_id'] : null;
        $where = $surveyId ? 'WHERE sa.survey_id = ?' : '';
        $params = $surveyId ? [$surveyId] : [];

        $stmt = $pdo->prepare("SELECT COUNT(*) total, ROUND(AVG(rate),2) avg_rate,
          SUM(rate=5) five_star, SUM(rate=4) four_star, SUM(rate=3) three_star,
          SUM(rate=2) two_star, SUM(rate=1) one_star,
          SUM(satisfaction='very-satisfied') very_satisfied,
          SUM(satisfaction='satisfied') satisfied,
          SUM(satisfaction='neutral') neutral,
          SUM(satisfaction='dissatisfied') dissatisfied,
          SUM(satisfaction='very-dissatisfied') very_dissatisfied,
          SUM(newsletter=1) newsletter_yes FROM survey_answers sa $where");
        $stmt->execute($params);
        $stats = $stmt->fetch() ?: [];

        $listStmt = $pdo->prepare("SELECT sa.id, sa.name, sa.email, sa.rate, sa.satisfaction, sa.message, sa.newsletter, sa.submitted_at,
          s.title AS survey_title, e.title AS event_title
          FROM survey_answers sa LEFT JOIN surveys s ON s.id=sa.survey_id LEFT JOIN events e ON e.id=s.event_id
          $where ORDER BY sa.submitted_at DESC, sa.id DESC LIMIT 500");
        $listStmt->execute($params);
        respond(true, '', ['stats'=>$stats, 'answers'=>$listStmt->fetchAll()]);
    }

    $stmt = $pdo->query("SELECT s.id,s.title,s.event_id,s.is_active,e.title AS event_title
      FROM surveys s LEFT JOIN events e ON e.id=s.event_id WHERE s.is_active=1 ORDER BY s.id DESC");
    respond(true, '', ['surveys'=>$stmt->fetchAll()]);
}

if ($method === 'POST') {
    $data = json_input();
    $name = trim((string)($data['name'] ?? ''));
    $email = strtolower(trim((string)($data['email'] ?? '')));
    $rate = (int)($data['rate'] ?? 0);
    $satisfaction = trim((string)($data['satisfaction'] ?? 'neutral'));
    $message = trim((string)($data['message'] ?? ''));
    $newsletter = !empty($data['newsletter']) ? 1 : 0;
    $surveyId = isset($data['survey_id']) && $data['survey_id'] !== '' ? (int)$data['survey_id'] : null;

    if ($name === '' || mb_strlen($name) > 150) respond(false,'نام واردشده معتبر نیست.',[],422);
    if (!filter_var($email,FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) respond(false,'ایمیل واردشده معتبر نیست.',[],422);
    if ($rate < 1 || $rate > 5) respond(false,'امتیاز باید بین ۱ تا ۵ باشد.',[],422);
    $allowed = ['very-satisfied','satisfied','neutral','dissatisfied','very-dissatisfied'];
    if (!in_array($satisfaction,$allowed,true)) respond(false,'میزان رضایت معتبر نیست.',[],422);
    if ($message === '' || mb_strlen($message) > 500) respond(false,'متن نظر باید بین ۱ تا ۵۰۰ کاراکتر باشد.',[],422);

    if ($surveyId !== null) {
        $s = $pdo->prepare('SELECT id FROM surveys WHERE id=? AND is_active=1');
        $s->execute([$surveyId]);
        if (!$s->fetchColumn()) respond(false,'نظرسنجی انتخاب‌شده فعال نیست.',[],404);
    } else {
        $surveyId = (int)($pdo->query('SELECT id FROM surveys WHERE is_active=1 ORDER BY id DESC LIMIT 1')->fetchColumn() ?: 0);
        if ($surveyId === 0) {
            $pdo->exec("INSERT INTO surveys (title,is_active) VALUES ('نظرسنجی عمومی همایش یار',1)");
            $surveyId = (int)$pdo->lastInsertId();
        }
    }

    $dup = $pdo->prepare('SELECT id FROM survey_answers WHERE survey_id=? AND email=? LIMIT 1');
    $dup->execute([$surveyId,$email]);
    if ($dup->fetchColumn()) respond(false,'با این ایمیل قبلاً در این نظرسنجی شرکت کرده‌اید.',[],409);

    $stmt = $pdo->prepare('INSERT INTO survey_answers (survey_id,name,email,rate,satisfaction,message,newsletter) VALUES (?,?,?,?,?,?,?)');
    $stmt->execute([$surveyId,$name,$email,$rate,$satisfaction,$message,$newsletter]);
    respond(true,'نظر شما با موفقیت ثبت شد.',['answer_id'=>(int)$pdo->lastInsertId()]);
}

if ($method === 'PATCH') {
    require_role(['secretariat','admin']);
    $data = json_input();
    $id = (int)($data['id'] ?? 0);
    if ($id <= 0) respond(false,'شناسه نظرسنجی معتبر نیست.',[],422);
    $updates=[]; $params=[];
    if (array_key_exists('title',$data)) { $updates[]='title=?'; $params[]=trim((string)$data['title']); }
    if (array_key_exists('event_id',$data)) { $updates[]='event_id=?'; $params[]=$data['event_id'] === null || $data['event_id']==='' ? null : (int)$data['event_id']; }
    if (array_key_exists('is_active',$data)) { $updates[]='is_active=?'; $params[]=!empty($data['is_active']) ? 1 : 0; }
    if (!$updates) respond(false,'تغییری ارسال نشده است.',[],422);
    $params[]=$id;
    $stmt=$pdo->prepare('UPDATE surveys SET '.implode(',',$updates).' WHERE id=?');
    $stmt->execute($params);
    respond(true,'نظرسنجی به‌روزرسانی شد.');
}

http_response_code(405);
echo json_encode(['ok'=>false,'message'=>'Method Not Allowed'], JSON_UNESCAPED_UNICODE);
