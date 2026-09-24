<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth.php';
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['ok'=>false,'message'=>'Method Not Allowed'], JSON_UNESCAPED_UNICODE); exit; }
require_role(['secretariat','admin']);
$data=json_decode(file_get_contents('php://input'),true) ?: $_POST;$articleId=(int)($data['article_id']??0);if($articleId<1){http_response_code(422);echo json_encode(['ok'=>false,'message'=>'شناسه مقاله نامعتبر است.'],JSON_UNESCAPED_UNICODE);exit;}
$pdo=db();$stmt=$pdo->prepare('SELECT file_path,revision_file_path,final_decision FROM articles WHERE id=?');$stmt->execute([$articleId]);$a=$stmt->fetch();if(!$a){http_response_code(404);echo json_encode(['ok'=>false,'message'=>'مقاله یافت نشد.'],JSON_UNESCAPED_UNICODE);exit;}if(!empty($a['final_decision'])){http_response_code(409);echo json_encode(['ok'=>false,'message'=>'مقاله دارای تصمیم نهایی است و حذف آن مجاز نیست.'],JSON_UNESCAPED_UNICODE);exit;}
$pdo->prepare('DELETE FROM articles WHERE id=?')->execute([$articleId]);foreach([$a['file_path'],$a['revision_file_path']] as $rel){if($rel){$absolute=dirname(__DIR__,2).'/'.preg_replace('#^backend/#','',$rel);if(is_file($absolute))@unlink($absolute);}}
echo json_encode(['ok'=>true,'message'=>'مقاله حذف شد.'],JSON_UNESCAPED_UNICODE);
