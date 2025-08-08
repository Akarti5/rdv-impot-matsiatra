<?php
declare(strict_types=1);

function notifications_mark_read(int $id, int $userId): array {
  $pdo = db();
  try {
    $stmt = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?');
    $stmt->execute([$id, $userId]);
    return ['ok' => true, 'message' => 'Notification lue'];
  } catch (Throwable $e) {
    return ['ok' => false, 'message' => 'Erreur notification'];
  }
}
