<?php
/**
 * Admin endpoints (admin role required)
 *
 * GET    /admin/users              - list all users
 * POST   /admin/users              - create user (any role)
 * GET    /admin/users/{id}         - get user
 * PUT    /admin/users/{id}         - update user
 * DELETE /admin/users/{id}         - delete user
 * POST   /admin/users/{id}/toggle  - toggle active status
 *
 * GET    /admin/resumes            - list all resumes (paginated)
 * GET    /admin/resumes/{id}       - get any resume
 * DELETE /admin/resumes/{id}       - delete any resume
 *
 * GET    /admin/stats              - dashboard stats
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Resume.php';
require_once __DIR__ . '/../middleware/auth.php';

$db          = (new Database())->getConnection();
$auth        = new Auth($db);
$userModel   = new User($db);
$resumeModel = new Resume($db);

$currentUser = $auth->requireAdmin();

$method   = $_SERVER['REQUEST_METHOD'];
$resource = $GLOBALS['resource'] ?? '';   // users | resumes | stats
$resourceId = isset($GLOBALS['resource_id']) ? (int) $GLOBALS['resource_id'] : null;
$action   = $GLOBALS['action'] ?? null;   // toggle
$body     = json_decode(file_get_contents('php://input'), true) ?? [];

header('Content-Type: application/json');

// ------------------------------------------------------------------ //
//  Stats
// ------------------------------------------------------------------ //
if ($resource === 'stats') {
    $totalUsers   = (int) $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $totalAdmins  = (int) $db->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn();
    $totalResumes = (int) $db->query("SELECT COUNT(*) FROM resumes")->fetchColumn();
    $publicResumes = (int) $db->query("SELECT COUNT(*) FROM resumes WHERE is_public=1")->fetchColumn();
    $recentUsers  = (int) $db->query("SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn();

    echo json_encode([
        'success' => true,
        'data' => [
            'total_users'    => $totalUsers,
            'total_admins'   => $totalAdmins,
            'total_resumes'  => $totalResumes,
            'public_resumes' => $publicResumes,
            'new_users_30d'  => $recentUsers,
        ],
    ]);
    exit;
}

// ------------------------------------------------------------------ //
//  Resumes (admin access to all resumes)
// ------------------------------------------------------------------ //
if ($resource === 'resumes') {
    if ($method === 'GET' && !$resourceId) {
        $page    = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = min(100, max(1, (int) ($_GET['per_page'] ?? 20)));
        $result  = $resumeModel->getAll($page, $perPage);
        echo json_encode(['success' => true, 'data' => $result]);
        exit;
    }

    if ($method === 'GET' && $resourceId) {
        $resume = $resumeModel->getById($resourceId);
        if (!$resume) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Resume not found.']);
            exit;
        }
        echo json_encode(['success' => true, 'data' => $resume]);
        exit;
    }

    if ($method === 'DELETE' && $resourceId) {
        $deleted = $resumeModel->delete($resourceId);
        echo json_encode(['success' => $deleted, 'message' => $deleted ? 'Resume deleted.' : 'Resume not found.']);
        exit;
    }

    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

// ------------------------------------------------------------------ //
//  Users
// ------------------------------------------------------------------ //
if ($resource === 'users') {
    if (!$resourceId) {
        if ($method === 'GET') {
            $page    = max(1, (int) ($_GET['page'] ?? 1));
            $perPage = min(100, max(1, (int) ($_GET['per_page'] ?? 20)));
            $result  = $userModel->getAll($page, $perPage);
            echo json_encode(['success' => true, 'data' => $result]);
            exit;
        }

        if ($method === 'POST') {
            $required = ['username', 'email', 'password'];
            foreach ($required as $f) {
                if (empty($body[$f])) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => "Field '{$f}' is required."]);
                    exit;
                }
            }
            try {
                $newId = $userModel->create($body);
                $newUser = $userModel->getById($newId);
                http_response_code(201);
                echo json_encode(['success' => true, 'message' => 'User created.', 'data' => $newUser]);
            } catch (InvalidArgumentException $e) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit;
        }

        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
        exit;
    }

    // Single user operations
    $targetUser = $userModel->getById($resourceId);
    if (!$targetUser) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found.']);
        exit;
    }

    if ($method === 'GET') {
        echo json_encode(['success' => true, 'data' => $targetUser]);
        exit;
    }

    // Toggle active status
    if ($method === 'POST' && $action === 'toggle') {
        $newStatus = $targetUser['is_active'] ? 0 : 1;
        $userModel->update($resourceId, ['is_active' => $newStatus]);
        echo json_encode([
            'success'   => true,
            'message'   => $newStatus ? 'User activated.' : 'User deactivated.',
            'is_active' => $newStatus,
        ]);
        exit;
    }

    if ($method === 'PUT') {
        try {
            // Prevent admin from removing their own admin role
            if ((int) $currentUser['id'] === $resourceId && isset($body['role']) && $body['role'] !== 'admin') {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'You cannot change your own role.']);
                exit;
            }
            $updated = $userModel->update($resourceId, $body);
            $updatedUser = $userModel->getById($resourceId);
            echo json_encode([
                'success' => true,
                'message' => $updated ? 'User updated.' : 'Nothing changed.',
                'data'    => $updatedUser,
            ]);
        } catch (InvalidArgumentException $e) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    if ($method === 'DELETE') {
        if ((int) $currentUser['id'] === $resourceId) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'You cannot delete your own account.']);
            exit;
        }
        $userModel->delete($resourceId);
        echo json_encode(['success' => true, 'message' => 'User deleted.']);
        exit;
    }

    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

http_response_code(404);
echo json_encode(['success' => false, 'message' => 'Admin endpoint not found.']);
