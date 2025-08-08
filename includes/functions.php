<?php
declare(strict_types=1);

// --- Auth helpers ---
function currentUser(): ?array {
  if (!isset($_SESSION['user'])) return null;

  // Session timeout
  if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
    logout();
    return null;
  }
  $_SESSION['last_activity'] = time();
  return $_SESSION['user'];
}

function loginUser(array $user): void {
  $_SESSION['user'] = [
    'id' => (int)$user['id'],
    'nom' => (string)$user['nom'],
    'prenom' => (string)$user['prenom'],
    'email' => (string)$user['email'],
    'type_user' => (string)$user['type_user'],
    'status' => (string)$user['status'],
  ];
  $_SESSION['last_activity'] = time();
}

function logout(): void {
  $_SESSION = [];
  if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
  }
  session_destroy();
}

function requireLogin(?string $role = null): void {
  $user = currentUser();
  if (!$user) {
    header('Location: ' . BASE_URL . '?page=login');
    exit;
  }
  if ($role && $user['type_user'] !== $role) {
    header('Location: ' . BASE_URL);
    exit;
  }
}

// --- CSRF ---
function csrf_token(): string {
  if (empty($_SESSION[CSRF_TOKEN_KEY])) {
    $_SESSION[CSRF_TOKEN_KEY] = bin2hex(random_bytes(32));
  }
  return $_SESSION[CSRF_TOKEN_KEY];
}

function verify_csrf(?string $token): bool {
  return is_string($token) && hash_equals($_SESSION[CSRF_TOKEN_KEY] ?? '', $token);
}

// --- JSON response ---
function jsonResponse(array $data, int $code = 200): void {
  http_response_code($code);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($data);
  exit;
}

// --- Sanitization helpers ---
function s(string $v): string {
  return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}

function post(string $key, $default = null) {
  return $_POST[$key] ?? $default;
}

function get(string $key, $default = null) {
  return $_GET[$key] ?? $default;
}

// --- Email validation helper ---
function is_valid_email(string $email): bool {
  $email = trim($email);
  if ($email === '') return false;

  // Prefer native validator
  if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
    return true;
  }

  // Fallback regex (RFC 5322-inspired, pragmatic)
  return (bool)preg_match('/^[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}$/i', $email);
}

// --- Pagination helper ---
function paginateParams(): array {
  $page = max(1, (int)(get('page_num', 1)));
  $limit = max(1, min(100, (int)(get('limit', 10))));
  $offset = ($page - 1) * $limit;
  return [$limit, $offset];
}

// --- Security logging (basic) ---
function logSecurityEvent(string $message): void {
  $file = __DIR__ . '/../security.log';
  $line = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
  @file_put_contents($file, $line, FILE_APPEND);
}
