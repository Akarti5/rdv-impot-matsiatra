<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  requireLogin('agent');
  if (!verify_csrf($_POST['_csrf'] ?? null)) jsonResponse(['ok' => false, 'message' => 'CSRF token invalide'], 400);

  $agent_id = currentUser()['id'];
  $date = (string)($_POST['date'] ?? '');
  $start = (string)($_POST['heure_debut'] ?? '');
  $end = (string)($_POST['heure_fin'] ?? '');
  $max = max(1, (int)($_POST['max_appointments'] ?? 1));

  if (!$date || !$start || !$end) jsonResponse(['ok' => false, 'message' => 'Champs requis manquants'], 400);
  if ($end <= $start) jsonResponse(['ok' => false, 'message' => 'Heure de fin invalide'], 400);

  $pdo = db();
  try {
    $stmt = $pdo->prepare('INSERT INTO time_slots (agent_id, date, heure_debut, heure_fin, is_available, max_appointments) VALUES (?,?,?,?,1,?)');
    $stmt->execute([$agent_id, $date, $start, $end, $max]);
    jsonResponse(['ok' => true, 'message' => 'Créneau ajouté']);
  } catch (Throwable $e) {
    jsonResponse(['ok' => false, 'message' => 'Erreur lors de l’ajout du créneau'], 500);
  }
}
