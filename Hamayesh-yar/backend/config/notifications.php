<?php
declare(strict_types=1);

function notify_user(PDO $pdo, int $userId, string $title, string $message, string $type = 'info'): void
{
    if ($userId < 1) return;
    $stmt = $pdo->prepare('INSERT INTO notifications (user_id,title,message,type) VALUES (?,?,?,?)');
    $stmt->execute([$userId, mb_substr($title, 0, 255), $message, mb_substr($type, 0, 50)]);
}

function notify_users(PDO $pdo, array $userIds, string $title, string $message, string $type = 'info'): void
{
    $userIds = array_values(array_unique(array_filter(array_map('intval', $userIds), static fn(int $id): bool => $id > 0)));
    if (!$userIds) return;
    $stmt = $pdo->prepare('INSERT INTO notifications (user_id,title,message,type) VALUES (?,?,?,?)');
    foreach ($userIds as $userId) {
        $stmt->execute([$userId, mb_substr($title, 0, 255), $message, mb_substr($type, 0, 50)]);
    }
}

function notify_roles(PDO $pdo, array $roles, string $title, string $message, string $type = 'info'): void
{
    $roles = array_values(array_unique(array_filter(array_map('strval', $roles))));
    if (!$roles) return;
    $placeholders = implode(',', array_fill(0, count($roles), '?'));
    $stmt = $pdo->prepare("SELECT id FROM users WHERE status='active' AND role IN ($placeholders)");
    $stmt->execute($roles);
    $notifyUsers = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    notify_users($pdo, $notifyUsers, $title, $message, $type);
}
