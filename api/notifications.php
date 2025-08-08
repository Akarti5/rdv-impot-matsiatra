<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../crud/notifications/read.php';
require_once __DIR__ . '/../crud/notifications/create.php';
require_once __DIR__ . '/../crud/notifications/update.php';
require_once __DIR__ . '/../crud/notifications/delete.php';

$action = $_GET['action'] ?? '';

try {
  requireLogin();
  $user = currentUser();

  if ($action === 'list') {
    $items = notifications_read_user($user['id']);
    jsonResponse(['ok' => true, 'items' => $items]);
  }

  if ($action === 'mark-read') {
    if (!verify_csrf($_POST['_csrf'] ?? null)) jsonResponse(['ok' => false, 'message' => 'CSRF token invalide'], 400);
    $id = (int)($_POST['id'] ?? 0);
    $res = notifications_mark_read($id, $user['id']);
    jsonResponse($res, $res['ok'] ? 200 : 400);
  }

  if ($action === 'clear-old') {
    if (!verify_csrf($_POST['_csrf'] ?? null)) jsonResponse(['ok' => false, 'message' => 'CSRF token invalide'], 400);
    $res = notifications_clear_old($user['id']);
    jsonResponse($res, $res['ok'] ? 200 : 400);
  }

  jsonResponse(['ok' => false, 'message' => 'Action inconnue'], 404);
} catch (Throwable $e) {
  jsonResponse(['ok' => false, 'message' => 'Erreur serveur'], 500);
}
