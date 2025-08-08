<?php
declare(strict_types=1);

function notifications_read_user(int $userId): array {
  $pdo = db();
  $stmt = $pdo->prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY sent_at DESC LIMIT 100');
  $stmt->execute([$userId]);
  return $stmt->fetchAll();
}
