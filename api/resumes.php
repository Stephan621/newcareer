<?php
/**
 * Resume endpoints
 *
 * GET    /resumes              - list user's resumes
 * POST   /resumes              - create resume
 * GET    /resumes/{id}         - get resume
 * PUT    /resumes/{id}         - update resume
 * DELETE /resumes/{id}         - delete resume
 *
 * POST   /resumes/{id}/education          - add education
 * PUT    /resumes/{id}/education/{eid}    - update education
 * DELETE /resumes/{id}/education/{eid}    - delete education
 *
 * POST   /resumes/{id}/experience         - add experience
 * PUT    /resumes/{id}/experience/{xid}   - update experience
 * DELETE /resumes/{id}/experience/{xid}  - delete experience
 *
 * POST   /resumes/{id}/skills             - add skill
 * PUT    /resumes/{id}/skills/{sid}       - update skill
 * DELETE /resumes/{id}/skills/{sid}       - delete skill
 *
 * POST   /resumes/{id}/projects           - add project
 * PUT    /resumes/{id}/projects/{pid}     - update project
 * DELETE /resumes/{id}/projects/{pid}     - delete project
 *
 * POST   /resumes/{id}/certifications          - add certification
 * PUT    /resumes/{id}/certifications/{cid}    - update certification
 * DELETE /resumes/{id}/certifications/{cid}    - delete certification
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Resume.php';
require_once __DIR__ . '/../middleware/auth.php';

$db         = (new Database())->getConnection();
$auth       = new Auth($db);
$resumeModel = new Resume($db);

$currentUser = $auth->requireAuth();

$method   = $_SERVER['REQUEST_METHOD'];
$resumeId = isset($GLOBALS['id']) ? (int) $GLOBALS['id'] : null;
$sub      = $GLOBALS['sub'] ?? null;   // education | experience | skills | projects | certifications
$subId    = isset($GLOBALS['sub_id']) ? (int) $GLOBALS['sub_id'] : null;
$body     = json_decode(file_get_contents('php://input'), true) ?? [];

header('Content-Type: application/json');

// Admins can access all resumes; regular users only their own
$isAdmin = $currentUser['role'] === 'admin';

// ------------------------------------------------------------------ //
//  Sub-resource helpers
// ------------------------------------------------------------------ //
if ($resumeId && $sub) {
    // Verify resume ownership (or admin)
    if (!$isAdmin && !$resumeModel->belongsToUser($resumeId, (int) $currentUser['id'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied.']);
        exit;
    }

    $subMap = [
        'education'      => ['add' => 'addEducation',      'update' => 'updateEducation',      'delete' => 'deleteEducation'],
        'experience'     => ['add' => 'addExperience',     'update' => 'updateExperience',     'delete' => 'deleteExperience'],
        'skills'         => ['add' => 'addSkill',          'update' => 'updateSkill',          'delete' => 'deleteSkill'],
        'projects'       => ['add' => 'addProject',        'update' => 'updateProject',        'delete' => 'deleteProject'],
        'certifications' => ['add' => 'addCertification',  'update' => 'updateCertification',  'delete' => 'deleteCertification'],
    ];

    if (!isset($subMap[$sub])) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Sub-resource not found.']);
        exit;
    }

    $ops = $subMap[$sub];

    if ($method === 'POST' && !$subId) {
        // Validate required fields per sub-resource
        $requiredFields = [
            'education'      => ['institution'],
            'experience'     => ['company', 'position'],
            'skills'         => ['name'],
            'projects'       => ['name'],
            'certifications' => ['name'],
        ];
        foreach ($requiredFields[$sub] as $f) {
            if (empty($body[$f])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => "Field '{$f}' is required."]);
                exit;
            }
        }
        $newId = $resumeModel->{$ops['add']}($resumeId, $body);
        http_response_code(201);
        echo json_encode(['success' => true, 'message' => ucfirst(rtrim($sub,'s')) . ' added.', 'id' => $newId]);
        exit;
    }

    if ($method === 'PUT' && $subId) {
        $updated = $resumeModel->{$ops['update']}($subId, $body);
        echo json_encode(['success' => $updated, 'message' => $updated ? 'Updated.' : 'Nothing to update or not found.']);
        exit;
    }

    if ($method === 'DELETE' && $subId) {
        $deleted = $resumeModel->{$ops['delete']}($subId);
        echo json_encode(['success' => $deleted, 'message' => $deleted ? 'Deleted.' : 'Not found.']);
        exit;
    }

    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

// ------------------------------------------------------------------ //
//  Core resume CRUD
// ------------------------------------------------------------------ //
if (!$resumeId) {
    if ($method === 'GET') {
        // List resumes
        if ($isAdmin && isset($_GET['all'])) {
            $page    = max(1, (int) ($_GET['page'] ?? 1));
            $perPage = min(100, max(1, (int) ($_GET['per_page'] ?? 20)));
            echo json_encode(['success' => true, 'data' => $resumeModel->getAll($page, $perPage)]);
        } else {
            $resumes = $resumeModel->getByUserId((int) $currentUser['id']);
            echo json_encode(['success' => true, 'data' => $resumes]);
        }
        exit;
    }

    if ($method === 'POST') {
        // Create resume
        $required = ['first_name', 'last_name', 'email'];
        foreach ($required as $f) {
            if (empty($body[$f])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => "Field '{$f}' is required."]);
                exit;
            }
        }
        if (!filter_var($body['email'], FILTER_VALIDATE_EMAIL)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Invalid email address.']);
            exit;
        }
        $newId = $resumeModel->create((int) $currentUser['id'], $body);
        http_response_code(201);
        $resume = $resumeModel->getById($newId);
        echo json_encode(['success' => true, 'message' => 'Resume created.', 'data' => $resume]);
        exit;
    }

    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

// Single resume operations
$resume = $resumeModel->getById($resumeId);
if (!$resume) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Resume not found.']);
    exit;
}

// Check ownership
if (!$isAdmin && (int) $resume['user_id'] !== (int) $currentUser['id']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied.']);
    exit;
}

if ($method === 'GET') {
    echo json_encode(['success' => true, 'data' => $resume]);
    exit;
}

if ($method === 'PUT') {
    if (!empty($body['email']) && !filter_var($body['email'], FILTER_VALIDATE_EMAIL)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Invalid email address.']);
        exit;
    }
    $updated = $resumeModel->update($resumeId, $body);
    $resume  = $resumeModel->getById($resumeId);
    echo json_encode(['success' => true, 'message' => $updated ? 'Resume updated.' : 'Nothing changed.', 'data' => $resume]);
    exit;
}

if ($method === 'DELETE') {
    $resumeModel->delete($resumeId);
    echo json_encode(['success' => true, 'message' => 'Resume deleted.']);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
