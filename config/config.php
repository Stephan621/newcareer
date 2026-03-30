<?php
// Application configuration
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'newcareer');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');

define('APP_NAME', 'Newcareer');
define('APP_URL', getenv('APP_URL') ?: 'http://localhost');
define('SESSION_LIFETIME', 3600);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
