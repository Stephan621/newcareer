<?php
/**
 * Authentication helper functions.
 *
 * Assumes config/config.php has already been included (session already started).
 */

require_once dirname(__DIR__, 2) . '/src/models/User.php';

// ── CSRF ──────────────────────────────────────────────────────────────────────

/** Generate (or return existing) CSRF token for the current session. */
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(CSRF_TOKEN_LENGTH));
    }
    return $_SESSION['csrf_token'];
}

/** Validate a submitted CSRF token. */
function verifyCsrf(string $token): bool
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/** Output a hidden CSRF input field. */
function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') . '">';
}

// ── Authentication state ──────────────────────────────────────────────────────

/** Return true if there is an authenticated user in session. */
function isLoggedIn(): bool
{
    return !empty($_SESSION['user_id']);
}

/** Return true if the logged-in user has the "admin" role. */
function isAdmin(): bool
{
    return isLoggedIn() && (($_SESSION['user_role'] ?? '') === 'admin');
}

/** Redirect to login if not authenticated. */
function requireLogin(): void
{
    if (!isLoggedIn()) {
        // Build an absolute-ish path to login.php by traversing from APP_URL
        $next = urlencode($_SERVER['REQUEST_URI']);
        redirect(APP_URL . '/public/login.php?next=' . $next);
    }
}

/** Redirect to login/home if not an admin. */
function requireAdmin(): void
{
    if (!isLoggedIn()) {
        $next = urlencode($_SERVER['REQUEST_URI']);
        redirect(APP_URL . '/public/login.php?next=' . $next);
    }
    if (!isAdmin()) {
        redirect(APP_URL . '/public/dashboard.php');
    }
}

// ── Session helpers ───────────────────────────────────────────────────────────

/**
 * Persist user data into the session after a successful authentication.
 */
function login(array $user): void
{
    // Regenerate session ID to prevent session fixation
    session_regenerate_id(true);

    $_SESSION['user_id']       = $user['id'];
    $_SESSION['user_username'] = $user['username'];
    $_SESSION['user_email']    = $user['email'];
    $_SESSION['user_role']     = $user['role'];
}

/** Destroy the current session and redirect to the login page. */
function logout(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
    redirect(APP_URL . '/public/login.php');
}

/**
 * Return the currently authenticated user array, or null.
 * Refreshes session data from the database on first call per request.
 */
function getCurrentUser(): ?array
{
    if (!isLoggedIn()) {
        return null;
    }

    // Build a lightweight array from session data to avoid a DB hit on every request.
    return [
        'id'       => (int) $_SESSION['user_id'],
        'username' => $_SESSION['user_username'],
        'email'    => $_SESSION['user_email'],
        'role'     => $_SESSION['user_role'],
    ];
}
