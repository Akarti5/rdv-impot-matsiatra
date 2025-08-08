<?php
declare(strict_types=1);
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

$pdo = db();
$mine = isset($_GET['mine']) ? (bool)$_GET['mine'] : false;
$future = isset($_GET['future']) ? (bool)$_GET['future'] : false;
$date = $_GET['date'] ?? null;
$agentId = isset($_GET['agent_id']) ? (int)$_GET['agent_id'] : null;
$onlyAvailable = isset($_GET['available']) ? (bool)$_GET['available'] : false;

try {
  $params = [];
  $where = ['1=1'];
  if ($mine) {
    requireLogin('agent');
    $where[] = 'agent_id = ?';
    $params[] = currentUser()['id'];
  } elseif ($agentId) {
    $where[] = 'agent_id = ?';
    $params[] = $agentId;
  }
  if ($date) { $where[] = 'date = ?'; $params[] = $date; }
  if ($future) { $where[] = 'date >= ?'; $params[] = date('Y-m-d'); }

  $sql = 'SELECT * FROM time_slots WHERE ' . implode(' AND ', $where) . ' ORDER BY date, heure_debut';
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $rows = $stmt->fetchAll();

  // Attach availability info
  $slots = [];
  foreach ($rows as $r) {
    $occupied = (int)$pdo->prepare('SELECT COUNT(*) FROM appointments WHERE agent_id = ? AND date_rdv = ? AND heure_rdv >= ? AND heure_rdv < ? AND status <> "cancelled"')
      ->execute([$r['agent_id'], $r['date'], $r['heure_debut'], $r['heure_fin']]) ? 0 : 0; // Workaround for chaining

    $stmt2 = $pdo->prepare('SELECT COUNT(*) FROM appointments WHERE agent_id = ? AND date_rdv = ? AND heure_rdv >= ? AND heure_rdv < ? AND status <> "cancelled"');
    $stmt2->execute([$r['agent_id'], $r['date'], $r['heure_debut'], $r['heure_fin']]);
    $count = (int)$stmt2->fetchColumn();

    $available = $r['is_available'] && ($count < (int)$r['max_appointments']);
    if (!$onlyAvailable || $available) {
      $slots[] = [
        'id' => (int)$r['id'],
        'agent_id' => (int)$r['agent_id'],
        'date' => $r['date'],
        'heure_debut' => substr($r['heure_debut'], 0, 5),
        'heure_fin' => substr($r['heure_fin'], 0, 5),
        'is_available' => (bool)$r['is_available'],
        'max_appointments' => (int)$r['max_appointments'],
        'occupied' => $count,
        'available' => $available,
      ];
    }
  }

  jsonResponse(['ok' => true, 'slots' => $slots]);
} catch (Throwable $e) {
  jsonResponse(['ok' => false, 'message' => 'Erreur de lecture des créneaux'], 500);
}
