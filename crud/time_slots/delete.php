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
  $pdo = db();
  try {
    $stmt = $pdo->prepare('DELETE FROM time_slots WHERE id = ? AND agent_id = ?');
    $stmt->execute([$id, currentUser()['id']]);
    jsonResponse(['ok' => true, 'message' => 'Créneau supprimé']);
  } catch (Throwable $e) {
    jsonResponse(['ok' => false, 'message' => 'Erreur lors de la suppression'], 500);
  }
}
