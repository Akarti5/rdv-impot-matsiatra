<?php
declare(strict_types=1);

// Recommended security flags (set BEFORE session_start; prefer php.ini in production)
@ini_set('session.use_strict_mode', '1');
@ini_set('session.cookie_httponly', '1');

// Start session if not already active
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

// Simple router: ?page=...
$page = $_GET['page'] ?? 'home';

include __DIR__ . '/includes/header.php';

switch ($page) {
  case 'login':
    include __DIR__ . '/pages/login.php';
    break;
  case 'register':
    include __DIR__ . '/pages/register.php';
    break;
  case 'dashboard-client':
    requireLogin('client');
    include __DIR__ . '/pages/dashboard-client.php';
    break;
  case 'dashboard-agent':
    requireLogin('agent');
    include __DIR__ . '/pages/dashboard-agent.php';
    break;
  default:
    include __DIR__ . '/pages/home.php';
}

include __DIR__ . '/includes/footer.php';
