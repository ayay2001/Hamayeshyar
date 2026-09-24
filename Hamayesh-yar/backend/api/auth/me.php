<?php
declare(strict_types=1);

require_once __DIR__ . '/../../middleware/auth.php';
header('Content-Type: application/json; charset=utf-8');

$user = require_auth();
echo json_encode(['ok' => true, 'user' => $user], JSON_UNESCAPED_UNICODE);
