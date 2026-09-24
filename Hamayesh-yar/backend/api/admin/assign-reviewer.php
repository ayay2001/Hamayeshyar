<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/notifications.php';
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['ok'=>false,'message'=>'Method Not Allowed'], JSON_UNESCAPED_UNICODE); exit; }
require_role(['secretariat','admin']);
$data=json_decode(file_get_contents('php://input'),true) ?: $_POST;
$articleId=(int)($data['article_id']??0); $reviewerId=(int)($data['reviewer_id']??0);
if($articleId<1||$reviewerId<1){http_response_code(422);echo json_encode(['ok'=>false,'message'=>'شناسه مقاله یا داور نامعتبر است.'],JSON_UNESCAPED_UNICODE);exit;}
$pdo=db();
$stmt=$pdo->prepare("SELECT id FROM users WHERE id=? AND role='reviewer' AND status='active' LIMIT 1");$stmt->execute([$reviewerId]);if(!$stmt->fetch()){http_response_code(404);echo json_encode(['ok'=>false,'message'=>'داور فعال یافت نشد.'],JSON_UNESCAPED_UNICODE);exit;}
$stmt=$pdo->prepare('SELECT id,status FROM articles WHERE id=? LIMIT 1');$stmt->execute([$articleId]);$article=$stmt->fetch();if(!$article){http_response_code(404);echo json_encode(['ok'=>false,'message'=>'مقاله یافت نشد.'],JSON_UNESCAPED_UNICODE);exit;}
try{$pdo->beginTransaction();$stmt=$pdo->prepare('INSERT INTO article_reviewers(article_id,reviewer_id) VALUES(?,?)');$stmt->execute([$articleId,$reviewerId]);if($article['status']==='pending'){$pdo->prepare("UPDATE articles SET status='reviewing' WHERE id=?")->execute([$articleId]);}$info=$pdo->prepare('SELECT a.title,a.author_id,u.name AS reviewer_name FROM articles a JOIN users u ON u.id=? WHERE a.id=? LIMIT 1');$info->execute([$reviewerId,$articleId]);$articleInfo=$info->fetch();if($articleInfo){notify_user($pdo,$reviewerId,'مقاله جدید برای داوری', 'مقاله «'.$articleInfo['title'].'» به شما برای داوری اختصاص داده شد.','review');notify_user($pdo,(int)$articleInfo['author_id'],'مقاله در حال داوری است','مقاله «'.$articleInfo['title'].'» برای داوری به یک داور اختصاص داده شد.','article');}$pdo->commit();}catch(PDOException $e){if($pdo->inTransaction())$pdo->rollBack();if((int)$e->errorInfo[1]===1062){http_response_code(409);echo json_encode(['ok'=>false,'message'=>'این داور قبلاً به مقاله اختصاص یافته است.'],JSON_UNESCAPED_UNICODE);exit;}http_response_code(500);echo json_encode(['ok'=>false,'message'=>'اختصاص داور انجام نشد.'],JSON_UNESCAPED_UNICODE);exit;}
echo json_encode(['ok'=>true,'message'=>'داور با موفقیت به مقاله اختصاص یافت.'],JSON_UNESCAPED_UNICODE);
