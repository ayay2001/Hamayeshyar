<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Method Not Allowed'], JSON_UNESCAPED_UNICODE);
    exit;
}

$user = require_role(['author']);

$stmt = db()->prepare(
    'SELECT id, event_id, title, abstract, keywords, file_path, revision_file_path, status, final_decision, submitted_at, revision_submitted_at, updated_at
     FROM articles
     WHERE author_id = ?
     ORDER BY submitted_at DESC, id DESC'
);
$stmt->execute([(int)$user['id']]);
$rows = $stmt->fetchAll();

$reviewStmt = db()->prepare('SELECT reviewer_id, score, comment, suggestion, reviewed_at FROM reviews WHERE article_id = ? ORDER BY id');
foreach ($rows as &$article) {
    $reviewStmt->execute([(int)$article['id']]);
    $reviews = $reviewStmt->fetchAll();
    $article['reviews'] = array_map(static function (array $r): array {
        return [
            'reviewerId' => (int)$r['reviewer_id'],
            'score' => (int)$r['score'],
            'comment' => $r['comment'],
            'suggestion' => $r['suggestion'],
            'date' => $r['reviewed_at']
        ];
    }, $reviews);
}
unset($article);

echo json_encode(['ok' => true, 'articles' => $rows], JSON_UNESCAPED_UNICODE);
