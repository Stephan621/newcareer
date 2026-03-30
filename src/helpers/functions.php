<?php
/**
 * General-purpose helper functions.
 *
 * Assumes config/config.php has already been included (session started).
 */

// ── Input sanitization ────────────────────────────────────────────────────────

/**
 * Strip tags and encode HTML special characters from a string.
 */
function sanitize(string $value): string
{
    return htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES, 'UTF-8');
}

// ── Navigation ────────────────────────────────────────────────────────────────

/**
 * Redirect to the given URL and exit.
 * The URL may be absolute or relative.
 */
function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

// ── Flash messages ────────────────────────────────────────────────────────────

/**
 * Store a flash message in the session.
 *
 * @param string $type    One of: success, error, warning, info
 * @param string $message Human-readable message text.
 */
function flashMessage(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/**
 * Retrieve and remove a flash message from the session.
 *
 * @return array{type: string, message: string}|null
 */
function getFlashMessage(): ?array
{
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Render the flash message HTML if one exists.
 */
function renderFlash(): void
{
    $flash = getFlashMessage();
    if ($flash !== null) {
        $type    = htmlspecialchars($flash['type'],    ENT_QUOTES, 'UTF-8');
        $message = htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8');
        echo '<div class="alert alert-' . $type . '" role="alert" id="flash-message">' . $message . '</div>';
    }
}

// ── Date formatting ───────────────────────────────────────────────────────────

/**
 * Format a MySQL DATETIME string for display.
 */
function formatDate(string $datetime, string $format = DATE_FORMAT): string
{
    try {
        $dt = new DateTime($datetime);
        return $dt->format($format);
    } catch (Exception $e) {
        return $datetime;
    }
}

/**
 * Return a human-friendly "time ago" string.
 */
function timeAgo(string $datetime): string
{
    try {
        $then = new DateTime($datetime);
        $now  = new DateTime();
        $diff = $now->diff($then);

        if ($diff->y > 0) return $diff->y . ' year'   . ($diff->y > 1 ? 's' : '') . ' ago';
        if ($diff->m > 0) return $diff->m . ' month'  . ($diff->m > 1 ? 's' : '') . ' ago';
        if ($diff->d > 0) return $diff->d . ' day'    . ($diff->d > 1 ? 's' : '') . ' ago';
        if ($diff->h > 0) return $diff->h . ' hour'   . ($diff->h > 1 ? 's' : '') . ' ago';
        if ($diff->i > 0) return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
        return 'just now';
    } catch (Exception $e) {
        return '';
    }
}

// ── String utilities ──────────────────────────────────────────────────────────

/**
 * Truncate a string to a given character limit, appending an ellipsis if needed.
 */
function truncate(string $text, int $limit = 150, string $append = '…'): string
{
    $text = strip_tags($text);
    if (mb_strlen($text) <= $limit) {
        return $text;
    }
    return rtrim(mb_substr($text, 0, $limit)) . $append;
}

/**
 * Convert newlines to <br> tags after sanitizing the text.
 */
function nl2brSafe(string $text): string
{
    return nl2br(htmlspecialchars($text, ENT_QUOTES, 'UTF-8'));
}

// ── Status badge ──────────────────────────────────────────────────────────────

/**
 * Return an HTML <span> badge for an application/job status.
 */
function statusBadge(string $status): string
{
    $map = [
        'pending'   => 'badge-warning',
        'reviewed'  => 'badge-info',
        'accepted'  => 'badge-success',
        'rejected'  => 'badge-danger',
        'active'    => 'badge-success',
        'inactive'  => 'badge-secondary',
        'full-time' => 'badge-primary',
        'part-time' => 'badge-info',
        'contract'  => 'badge-warning',
        'remote'    => 'badge-success',
    ];
    $class = $map[$status] ?? 'badge-secondary';
    return '<span class="badge ' . $class . '">' . htmlspecialchars(ucfirst($status), ENT_QUOTES, 'UTF-8') . '</span>';
}

/**
 * Build a query-string that merges the current GET params with new values.
 * Useful for pagination / filter links.
 */
function buildQueryString(array $extra = []): string
{
    $params = array_merge($_GET, $extra);
    return '?' . http_build_query($params);
}
