<?php
declare(strict_types=1);

function users_soft_delete(int $id, array $actor): array {
  // Only admins could delete others; not implemented. Allow self deactivation.
  if ($actor['id'] !== $id) return ['ok' => false, 'message' => 'Non autorisé'];
  $pdo = db();
  try {
    $stmt = $pdo->prepare("UPDATE users SET status='inactive' WHERE id = ?");
    $stmt->execute([$id]);
    return ['ok' => true, 'message' => 'Compte désactivé'];
  } catch (Throwable $e) {
    return ['ok' => false, 'message' => 'Erreur lors de la désactivation'];
  }
}
