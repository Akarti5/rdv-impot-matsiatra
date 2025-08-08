<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  requireLogin('agent');
  if (!verify_csrf($_POST['_csrf'] ?? null)) jsonResponse(['ok' => false, 'message' => 'CSRF token invalide'], 400);

  $id = (int)($_POST['id'] ?? 0);
  $toggle = isset($_POST['toggle']);
  $pdo = db();

  try {
    // Verify ownership
    $stmt = $pdo->prepare('SELECT agent_id, is_available FROM time_slots WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row || (int)$row['agent_id'] !== currentUser()['id']) jsonResponse(['ok' => false, 'message' => 'Non autorisé'], 403);

    if ($toggle) {
      $new = $row['is_available'] ? 0 : 1;
      $stmt2 = $pdo->prepare('UPDATE time_slots SET is_available = ? WHERE id = ?');
      $stmt2->execute([$new, $id]);
    } else {
      $fields = [];
      $params = [];
      if (isset($_POST['max_appointments'])) { $fields[] = 'max_appointments = ?'; $params[] = max(1, (int)$_POST['max_appointments']); }
      if ($fields) {
        $params[] = $id;
        $pdo->prepare('UPDATE time_slots SET ' . implode(', ', $fields) . ' WHERE id = ?')->execute($params);
      }
    }
    jsonResponse(['ok' => true, 'message' => 'Créneau mis à jour']);
  } catch (Throwable $e) {
    jsonResponse(['ok' => false, 'message' => 'Erreur lors de la mise à jour'], 500);
  }
}
