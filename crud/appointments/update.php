<?php
declare(strict_types=1);

function appointments_update_status(int $id, string $status, array $actor): array {
  if (!in_array($status, ['pending','confirmed','completed','cancelled'], true)) {
    return ['ok' => false, 'message' => 'Statut invalide'];
  }

  $pdo = db();
  try {
    // Fetch appointment
    $stmt = $pdo->prepare('SELECT * FROM appointments WHERE id = ?');
    $stmt->execute([$id]);
    $appt = $stmt->fetch();
    if (!$appt) return ['ok' => false, 'message' => 'Rendez-vous introuvable'];

    // Authorization: clients can cancel their own, agents can change their own
    if ($actor['type_user'] === 'client' && (int)$appt['user_id'] !== $actor['id']) {
      return ['ok' => false, 'message' => 'Non autorisé'];
    }
    if ($actor['type_user'] === 'agent' && (int)$appt['agent_id'] !== $actor['id']) {
      return ['ok' => false, 'message' => 'Non autorisé'];
    }
    if ($actor['type_user'] === 'client' && $status !== 'cancelled') {
      return ['ok' => false, 'message' => 'Action non autorisée'];
    }

    $stmt = $pdo->prepare('UPDATE appointments SET status = ? WHERE id = ?');
    $stmt->execute([$status, $id]);

    // Notification
    $targetUserId = $actor['type_user'] === 'agent' ? (int)$appt['user_id'] : (int)$appt['agent_id'];
    if ($targetUserId) {
      $msg = "Statut du RDV #{$id} mis à jour: {$status}";
      $n = $pdo->prepare('INSERT INTO notifications (user_id, message, type, is_read) VALUES (?,?, "system", 0)');
      $n->execute([$targetUserId, $msg]);
    }

    return ['ok' => true, 'message' => 'Statut mis à jour'];
  } catch (Throwable $e) {
    return ['ok' => false, 'message' => 'Erreur lors de la mise à jour'];
  }
}
