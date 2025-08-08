<?php
declare(strict_types=1);

// Application config
define('APP_NAME', 'RDV Impôts Matsiatra');
define('BASE_URL', '/rdv-impots-matsiatra/');

// Database config (adjust for your local setup)
define('DB_HOST', 'localhost');
define('DB_NAME', 'rdv_impots_matsiatra');
define('DB_USER', 'root');
define('DB_PASS', '');

// Session timeout (seconds)
define('SESSION_TIMEOUT', 60 * 60); // 1 hour

// CSRF token name
define('CSRF_TOKEN_KEY', '_csrf_token');

// Default timezone
date_default_timezone_set('Africa/Nairobi');
