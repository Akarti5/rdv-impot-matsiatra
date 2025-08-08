<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../crud/users/read.php';

$action = $_GET['action'] ?? '';

try {
  if ($action === 'list-agents') {
    $users = users_list(type: 'agent');
    jsonResponse(['ok' => true, 'users' => array_map(fn($u) => [
      'id' => (int)$u['id'],
      'nom' => $u['nom'],
      'prenom' => $u['prenom'],
    ], $users)]);
  }

  // Only agents can list clients (not used on client)
  if ($action === 'list-clients') {
    requireLogin('agent');
    $users = users_list(type: 'client');
    jsonResponse(['ok' => true, 'users' => $users]);
  }

  jsonResponse(['ok' => false, 'message' => 'Action inconnue'], 404);
} catch (Throwable $e) {
  jsonResponse(['ok' => false, 'message' => 'Erreur serveur'], 500);
}
