<?php
declare(strict_types=1);

function notifications_create(int $userId, string $message, string $type = 'system'): bool {
  $pdo = db();
  $stmt = $pdo->prepare('INSERT INTO notifications (user_id, message, type, is_read) VALUES (?,?,?,0)');
  return $stmt->execute([$userId, $message, $type]);
}
