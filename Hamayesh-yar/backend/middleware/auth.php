<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function require_auth(): array
{
    if (empty($_SESSION['user'])) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'message' => 'احراز هویت الزامی است.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    return $_SESSION['user'];
}

function require_role(array $roles): array
{
    $user = require_auth();

    if (!in_array($user['role'], $roles, true)) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'message' => 'شما دسترسی لازم را ندارید.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    return $user;
}
