<?php
declare(strict_types=1);

// Prevent any output before headers
ob_start();

// Handle session properly
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../crud/appointments/create.php';
require_once __DIR__ . '/../crud/appointments/read.php';
require_once __DIR__ . '/../crud/appointments/update.php';

$action = $_GET['action'] ?? '';

// Debug: Log the action being requested
error_log("Appointments API called with action: " . $action);

try {
  if ($action === 'create') {
    requireLogin('client');
    if (!verify_csrf($_POST['_csrf'] ?? null)) jsonResponse(['ok' => false, 'message' => 'CSRF token invalide'], 400);
    $payload = [
      'user_id' => currentUser()['id'],
      'agent_id' => (int)($_POST['agent_id'] ?? 0),
      'date_rdv' => (string)($_POST['date_rdv'] ?? ''),
      'heure_rdv' => (string)($_POST['heure_rdv'] ?? ''),
      'motif' => trim((string)($_POST['motif'] ?? '')),
      'notes_client' => trim((string)($_POST['notes_client'] ?? '')),
    ];
    $res = appointments_create($payload);
    jsonResponse($res, $res['ok'] ? 200 : 400);
  }

  if ($action === 'read') {
    $scope = $_GET['scope'] ?? 'mine';
    $range = $_GET['range'] ?? 'upcoming';
    if ($scope === 'mine') {
      requireLogin('client');
      $res = appointments_read_by_user(currentUser()['id']);
      jsonResponse(['ok' => true, 'appointments' => $res]);
    } else if ($scope === 'agent') {
      requireLogin('agent');
      $agentId = currentUser()['id'];
      $filter = $range;
      $res = appointments_read_by_agent($agentId, $filter);
      jsonResponse(['ok' => true, 'appointments' => $res]);
    } else {
      jsonResponse(['ok' => false, 'message' => 'Portée invalide'], 400);
    }
  }

  if ($action === 'cancel') {
    requireLogin('client');
    if (!verify_csrf($_POST['_csrf'] ?? null)) jsonResponse(['ok' => false, 'message' => 'CSRF token invalide'], 400);
    $id = (int)($_POST['id'] ?? 0);
    $res = appointments_update_status($id, 'cancelled', currentUser());
    jsonResponse($res, $res['ok'] ? 200 : 400);
  }

  if ($action === 'update-status') {
    requireLogin('agent');
    if (!verify_csrf($_POST['_csrf'] ?? null)) jsonResponse(['ok' => false, 'message' => 'CSRF token invalide'], 400);
    $id = (int)($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? '';
    $res = appointments_update_status($id, $status, currentUser());
    jsonResponse($res, $res['ok'] ? 200 : 400);
  }

  if ($action === 'types') {
    error_log("Types action executed");
    try {
      $pdo = db();
      $stmt = $pdo->query('SELECT id, nom_motif, description, duree_estimee FROM appointment_types ORDER BY nom_motif');
      $types = $stmt->fetchAll();
      error_log("Found " . count($types) . " appointment types");
      // Clear any output buffer before sending JSON
      ob_clean();
      jsonResponse(['ok' => true, 'types' => $types]);
    } catch (Throwable $e) {
      error_log("Error in types action: " . $e->getMessage());
      ob_clean();
      jsonResponse(['ok' => false, 'message' => 'Erreur lors du chargement des motifs'], 500);
    }
  }

  if ($action === 'stats-today') {
    $pdo = db();
    $today = date('Y-m-d');
    $count = (int)$pdo->query("SELECT COUNT(*) FROM appointments WHERE date_rdv = '{$today}'")->fetchColumn();
    $agents = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE type_user='agent' AND status='active'")->fetchColumn();
    jsonResponse(['ok' => true, 'stats' => ['appointments_today' => $count, 'agents' => $agents]]);
  }

  if ($action === 'stats-7d') {
    requireLogin('agent');
    $pdo = db();
    $labels = [];
    $series = [];
    for ($i = 6; $i >= 0; $i--) {
      $d = date('Y-m-d', strtotime("-{$i} days"));
      $labels[] = date('d/m', strtotime($d));
      $stmt = $pdo->prepare('SELECT COUNT(*) FROM appointments WHERE date_rdv = ? AND agent_id = ?');
      $stmt->execute([$d, currentUser()['id']]);
      $series[] = (int)$stmt->fetchColumn();
    }
    jsonResponse(['ok' => true, 'labels' => $labels, 'series' => $series]);
  }

  jsonResponse(['ok' => false, 'message' => 'Action inconnue'], 404);
} catch (Throwable $e) {
  jsonResponse(['ok' => false, 'message' => 'Erreur serveur'], 500);
}
