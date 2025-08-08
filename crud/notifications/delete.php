<?php
declare(strict_types=1);

function notifications_clear_old(int $userId): array {
  $pdo = db();
  try {
    $stmt = $pdo->prepare('DELETE FROM notifications WHERE user_id = ? AND sent_at < DATE_SUB(NOW(), INTERVAL 30 DAY)');
    $stmt->execute([$userId]);
    return ['ok' => true, 'message' => 'Anciennes notifications supprimées'];
  } catch (Throwable $e) {
    return ['ok' => false, 'message' => 'Erreur notification'];
  }
}
