<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/notifications.php';
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['ok'=>false,'message'=>'Method Not Allowed'], JSON_UNESCAPED_UNICODE); exit; }
require_role(['secretariat','admin']);
$data=json_decode(file_get_contents('php://input'),true) ?: $_POST;
$articleId=(int)($data['article_id']??0); $decision=strtolower(trim((string)($data['decision']??'')));
if($articleId<1||!in_array($decision,['accept','reject','revision'],true)){http_response_code(422);echo json_encode(['ok'=>false,'message'=>'تصمیم نهایی نامعتبر است.'],JSON_UNESCAPED_UNICODE);exit;}
$pdo=db();$stmt=$pdo->prepare('SELECT COUNT(*) FROM reviews WHERE article_id=?');$stmt->execute([$articleId]);if((int)$stmt->fetchColumn()<1){http_response_code(409);echo json_encode(['ok'=>false,'message'=>'این مقاله هنوز هیچ داوری ثبت‌شده‌ای ندارد.'],JSON_UNESCAPED_UNICODE);exit;}
$info=$pdo->prepare('SELECT title,author_id FROM articles WHERE id=? LIMIT 1');$info->execute([$articleId]);$article=$info->fetch();if(!$article){http_response_code(404);echo json_encode(['ok'=>false,'message'=>'مقاله یافت نشد.'],JSON_UNESCAPED_UNICODE);exit;}
$stmt=$pdo->prepare('UPDATE articles SET final_decision=?, status=? WHERE id=?');$stmt->execute([$decision,$decision,$articleId]);
$decisionText=['accept'=>'پذیرفته شد','reject'=>'رد شد','revision'=>'نیازمند اصلاح است'][$decision];
notify_user($pdo,(int)$article['author_id'],'نتیجه نهایی مقاله','نتیجه نهایی مقاله «'.$article['title'].'»: '.$decisionText.'.','decision');if($stmt->rowCount()===0){$stmt=$pdo->prepare('SELECT id FROM articles WHERE id=?');$stmt->execute([$articleId]);if(!$stmt->fetch()){http_response_code(404);echo json_encode(['ok'=>false,'message'=>'مقاله یافت نشد.'],JSON_UNESCAPED_UNICODE);exit;}}
echo json_encode(['ok'=>true,'message'=>'تصمیم نهایی ثبت شد.'],JSON_UNESCAPED_UNICODE);
