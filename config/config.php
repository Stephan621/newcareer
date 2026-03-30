<?php
/**
 * Application-wide configuration.
 * Must be included before any output is sent.
 */

// ── Application ──────────────────────────────────────────────────────────────
define('APP_NAME',    'NewCareer');
define('APP_VERSION', '1.0.0');
define('APP_URL',     getenv('APP_URL') ?: 'http://localhost');

// ── Paths (no trailing slash) ─────────────────────────────────────────────────
define('ROOT_PATH',   dirname(__DIR__));
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('SRC_PATH',    ROOT_PATH . '/src');
define('CONFIG_PATH', ROOT_PATH . '/config');

// ── Session ───────────────────────────────────────────────────────────────────
define('SESSION_NAME',     'newcareer_session');
define('SESSION_LIFETIME', 7200);   // 2 hours in seconds

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.name',            SESSION_NAME);
    ini_set('session.gc_maxlifetime',  (string) SESSION_LIFETIME);
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_strict_mode', '1');
    // Set SameSite=Lax via cookie params (PHP 7.3+)
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path'     => '/',
        'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// ── Security ──────────────────────────────────────────────────────────────────
define('CSRF_TOKEN_LENGTH', 32);
define('PASSWORD_ALGO',     PASSWORD_BCRYPT);
define('PASSWORD_OPTIONS',  ['cost' => 12]);

// ── Pagination ────────────────────────────────────────────────────────────────
define('ITEMS_PER_PAGE', 10);

// ── Date / time ───────────────────────────────────────────────────────────────
define('DATE_FORMAT',      'M j, Y');
define('DATETIME_FORMAT',  'M j, Y g:i A');
define('TIMEZONE',         'UTC');
date_default_timezone_set(TIMEZONE);

// ── Error reporting (disable in production) ───────────────────────────────────
if (getenv('APP_ENV') === 'production') {
    ini_set('display_errors', '0');
    error_reporting(0);
} else {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}
