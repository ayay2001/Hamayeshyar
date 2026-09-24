<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth.php';
header('Content-Type: application/json; charset=utf-8');
require_role(['secretariat','admin']);

$pdo = db();
$eventId = isset($_GET['event_id']) && $_GET['event_id'] !== '' ? (int)$_GET['event_id'] : null;
$whereArticle = $eventId ? 'WHERE a.event_id = ?' : '';
$whereReg = $eventId ? 'WHERE r.event_id = ?' : '';
$params = $eventId ? [$eventId] : [];

$articlesStmt = $pdo->prepare("SELECT COUNT(*) total,
 SUM(a.final_decision='accept') accepted,
 SUM(a.final_decision='reject') rejected,
 SUM(a.final_decision='revision') revision,
 SUM(a.final_decision IS NULL) pending,
 SUM(a.status='reviewing') reviewing,
 SUM(a.status='reviewed') reviewed
 FROM articles a $whereArticle");
$articlesStmt->execute($params); $articles=$articlesStmt->fetch() ?: [];

$regsStmt = $pdo->prepare("SELECT COUNT(*) total,
 SUM(r.status='registered') registered,
 SUM(r.status='attended') attended,
 SUM(r.status='cancelled') cancelled
 FROM registrations r $whereReg");
$regsStmt->execute($params); $registrations=$regsStmt->fetch() ?: [];

$surveyWhere = $eventId ? 'WHERE s.event_id = ?' : '';
$surveyStmt = $pdo->prepare("SELECT COUNT(sa.id) total_answers,
 ROUND(AVG(sa.rate),2) avg_rate,
 SUM(sa.rate=5) five_star,SUM(sa.rate=4) four_star,SUM(sa.rate=3) three_star,SUM(sa.rate=2) two_star,SUM(sa.rate=1) one_star,
 SUM(sa.satisfaction='very-satisfied') very_satisfied,SUM(sa.satisfaction='satisfied') satisfied,
 SUM(sa.satisfaction='neutral') neutral,SUM(sa.satisfaction='dissatisfied') dissatisfied,SUM(sa.satisfaction='very-dissatisfied') very_dissatisfied
 FROM surveys s LEFT JOIN survey_answers sa ON sa.survey_id=s.id $surveyWhere");
$surveyStmt->execute($eventId ? [$eventId] : []); $survey=$surveyStmt->fetch() ?: [];

$listStmt = $pdo->prepare("SELECT a.id,a.title,u.name author_name,a.status,a.final_decision,a.submitted_at,e.title event_title
 FROM articles a JOIN users u ON u.id=a.author_id LEFT JOIN events e ON e.id=a.event_id $whereArticle ORDER BY a.submitted_at DESC,a.id DESC LIMIT 500");
$listStmt->execute($params); $articleList=$listStmt->fetchAll();

$regListStmt = $pdo->prepare("SELECT r.id,r.name,r.email,r.phone,r.organization,r.status,r.registered_at,e.title event_title
 FROM registrations r JOIN events e ON e.id=r.event_id $whereReg ORDER BY r.registered_at DESC,r.id DESC LIMIT 500");
$regListStmt->execute($params); $regList=$regListStmt->fetchAll();

$eventsStmt = $pdo->query('SELECT id,title,status,start_date FROM events ORDER BY start_date IS NULL,start_date DESC,id DESC');

echo json_encode(['ok'=>true,'filters'=>['event_id'=>$eventId],'articles'=>$articles,'registrations'=>$registrations,'survey'=>$survey,'article_list'=>$articleList,'registration_list'=>$regList,'events'=>$eventsStmt->fetchAll()],JSON_UNESCAPED_UNICODE);
