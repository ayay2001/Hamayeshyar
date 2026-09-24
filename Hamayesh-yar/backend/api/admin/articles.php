<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') { http_response_code(405); echo json_encode(['ok'=>false,'message'=>'Method Not Allowed'], JSON_UNESCAPED_UNICODE); exit; }
require_role(['secretariat','admin']);

$pdo = db();
$articles = $pdo->query('SELECT a.id,a.event_id,a.author_id,a.title,a.abstract,a.keywords,a.file_path,a.revision_file_path,a.status,a.final_decision,a.submitted_at,a.revision_submitted_at,u.name AS author_name FROM articles a JOIN users u ON u.id=a.author_id ORDER BY a.submitted_at DESC,a.id DESC')->fetchAll();
$reviewerStmt = $pdo->prepare("SELECT ar.reviewer_id, u.name AS reviewer_name, u.email AS reviewer_email, u.status AS reviewer_status FROM article_reviewers ar JOIN users u ON u.id=ar.reviewer_id WHERE ar.article_id=? ORDER BY ar.id");
$reviewStmt = $pdo->prepare('SELECT reviewer_id,score,comment,suggestion,reviewed_at FROM reviews WHERE article_id=? ORDER BY id');
foreach ($articles as &$a) {
  $reviewerStmt->execute([(int)$a['id']]);
  $reviewerRows = $reviewerStmt->fetchAll();
  $a['reviewers'] = array_map(static function(array $r): array { return ['id'=>(int)$r['reviewer_id'],'name'=>$r['reviewer_name'],'email'=>$r['reviewer_email'],'status'=>$r['reviewer_status']]; }, $reviewerRows);
  $reviewStmt->execute([(int)$a['id']]);
  $reviews = $reviewStmt->fetchAll();
  $a['reviews'] = array_map(static function(array $r): array { return ['reviewerId'=>(int)$r['reviewer_id'],'score'=>(int)$r['score'],'comment'=>$r['comment'],'suggestion'=>$r['suggestion'],'date'=>$r['reviewed_at']]; }, $reviews);
  $a['authorName'] = $a['author_name']; unset($a['author_name']);
  $a['finalDecision'] = $a['final_decision'];
}
unset($a);
echo json_encode(['ok'=>true,'articles'=>$articles], JSON_UNESCAPED_UNICODE);
