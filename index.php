<?php
/**
 * NewCareer Resume & Admin Backend — Front Controller
 *
 * Routes:
 *   POST   /auth/login
 *   POST   /auth/logout
 *   POST   /auth/register
 *
 *   GET    /resumes
 *   POST   /resumes
 *   GET    /resumes/{id}
 *   PUT    /resumes/{id}
 *   DELETE /resumes/{id}
 *   POST   /resumes/{id}/education
 *   PUT    /resumes/{id}/education/{eid}
 *   DELETE /resumes/{id}/education/{eid}
 *   POST   /resumes/{id}/experience
 *   PUT    /resumes/{id}/experience/{xid}
 *   DELETE /resumes/{id}/experience/{xid}
 *   POST   /resumes/{id}/skills
 *   PUT    /resumes/{id}/skills/{sid}
 *   DELETE /resumes/{id}/skills/{sid}
 *   POST   /resumes/{id}/projects
 *   PUT    /resumes/{id}/projects/{pid}
 *   DELETE /resumes/{id}/projects/{pid}
 *   POST   /resumes/{id}/certifications
 *   PUT    /resumes/{id}/certifications/{cid}
 *   DELETE /resumes/{id}/certifications/{cid}
 *
 *   GET    /admin/stats
 *   GET    /admin/users
 *   POST   /admin/users
 *   GET    /admin/users/{id}
 *   PUT    /admin/users/{id}
 *   DELETE /admin/users/{id}
 *   POST   /admin/users/{id}/toggle
 *   GET    /admin/resumes
 *   GET    /admin/resumes/{id}
 *   DELETE /admin/resumes/{id}
 */

// ---- CORS headers --------------------------------------------------------
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ---- Parse request URI ---------------------------------------------------
$requestUri  = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$scriptDir   = dirname($_SERVER['SCRIPT_NAME']);
$path        = '/' . ltrim(substr($requestUri, strlen($scriptDir)), '/');
$segments    = array_values(array_filter(explode('/', $path)));

// ---- Routing -------------------------------------------------------------

// /auth/{action}
if (isset($segments[0]) && $segments[0] === 'auth' && isset($segments[1])) {
    $GLOBALS['action'] = $segments[1];
    require __DIR__ . '/api/auth.php';
    exit;
}

// /resumes[/{id}[/{sub}[/{sub_id}]]]
if (isset($segments[0]) && $segments[0] === 'resumes') {
    $GLOBALS['id']     = $segments[1] ?? null;
    $GLOBALS['sub']    = $segments[2] ?? null;
    $GLOBALS['sub_id'] = $segments[3] ?? null;
    require __DIR__ . '/api/resumes.php';
    exit;
}

// /admin/{resource}[/{resource_id}[/{action}]]
if (isset($segments[0]) && $segments[0] === 'admin' && isset($segments[1])) {
    $GLOBALS['resource']    = $segments[1];
    $GLOBALS['resource_id'] = $segments[2] ?? null;
    $GLOBALS['action']      = $segments[3] ?? null;
    require __DIR__ . '/api/admin.php';
    exit;
}

// ---- 404 -----------------------------------------------------------------
http_response_code(404);
echo json_encode(['success' => false, 'message' => 'Endpoint not found.']);
