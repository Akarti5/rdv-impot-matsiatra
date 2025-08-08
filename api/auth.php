<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../crud/users/read.php';
require_once __DIR__ . '/../crud/users/create.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
  if ($action === 'login') {
    if (!verify_csrf($_POST['_csrf'] ?? null)) jsonResponse(['ok' => false, 'message' => 'CSRF token invalide'], 400);
    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    if (!$email || !$password) jsonResponse(['ok' => false, 'message' => 'Identifiants requis'], 400);

    $user = users_find_by_email($email);
    if (!$user || $user['status'] !== 'active' || !password_verify($password, $user['password_hash'])) {
      logSecurityEvent("Failed login for email {$email} from IP " . ($_SERVER['REMOTE_ADDR'] ?? ''));
      jsonResponse(['ok' => false, 'message' => 'Email ou mot de passe incorrect'], 401);
    }

    loginUser($user);

    $redirect = $user['type_user'] === 'agent' ? (BASE_URL . '?page=dashboard-agent') : (BASE_URL . '?page=dashboard-client');
    jsonResponse(['ok' => true, 'message' => 'Connecté', 'redirect' => $redirect]);
  }

  if ($action === 'logout') {
    if (!verify_csrf($_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) jsonResponse(['ok' => false, 'message' => 'CSRF token invalide'], 400);
    logout();
    jsonResponse(['ok' => true, 'message' => 'Déconnecté']);
  }

  if ($action === 'register') {
    if (!verify_csrf($_POST['_csrf'] ?? null)) jsonResponse(['ok' => false, 'message' => 'CSRF token invalide'], 400);
    $payload = [
      'nom' => trim((string)($_POST['nom'] ?? '')),
      'prenom' => trim((string)($_POST['prenom'] ?? '')),
      'email' => trim((string)($_POST['email'] ?? '')),
      'password' => (string)($_POST['password'] ?? ''),
      'password2' => (string)($_POST['password2'] ?? ''),
      'phone' => trim((string)($_POST['phone'] ?? '')),
      'nif' => trim((string)($_POST['nif'] ?? '')),
      'type_user' => in_array($_POST['type_user'] ?? 'client', ['client','agent'], true) ? $_POST['type_user'] : 'client',
    ];
    $res = users_create($payload);
    jsonResponse($res, $res['ok'] ? 200 : 400);
  }

  if ($action === 'me') {
    $u = currentUser();
    jsonResponse(['ok' => (bool)$u, 'user' => $u]);
  }

  jsonResponse(['ok' => false, 'message' => 'Action inconnue'], 404);
} catch (Throwable $e) {
  jsonResponse(['ok' => false, 'message' => 'Erreur serveur'], 500);
}
