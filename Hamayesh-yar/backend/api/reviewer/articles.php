<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth.php';
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'GET') { http_response_code(405); echo json_encode(['ok'=>false,'message'=>'Method Not Allowed'],JSON_UNESCAPED_UNICODE); exit; }
$user=require_role(['reviewer']);$pdo=db();
$stmt=$pdo->prepare('SELECT a.id,a.title,a.abstract,a.keywords,a.file_path,a.status,a.final_decision,u.name AS author_name FROM articles a JOIN article_reviewers ar ON ar.article_id=a.id JOIN users u ON u.id=a.author_id WHERE ar.reviewer_id=? ORDER BY a.submitted_at DESC,a.id DESC');$stmt->execute([(int)$user['id']]);$articles=$stmt->fetchAll();
$rstmt=$pdo->prepare('SELECT reviewer_id,score,comment,suggestion,reviewed_at FROM reviews WHERE article_id=? AND reviewer_id=? LIMIT 1');
foreach($articles as &$a){$rstmt->execute([(int)$a['id'],(int)$user['id']]);$r=$rstmt->fetch();$a['authorName']=$a['author_name'];unset($a['author_name']);$a['myReview']=$r?['reviewerId'=>(int)$r['reviewer_id'],'score'=>(int)$r['score'],'comment'=>$r['comment'],'suggestion'=>$r['suggestion'],'date'=>$r['reviewed_at']]:null;}
unset($a);echo json_encode(['ok'=>true,'articles'=>$articles],JSON_UNESCAPED_UNICODE);
