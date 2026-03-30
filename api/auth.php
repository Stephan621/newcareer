<?php
/**
 * Auth endpoints: POST /auth/login, POST /auth/logout, POST /auth/register
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../middleware/auth.php';

$db   = (new Database())->getConnection();
$auth = new Auth($db);
$user = new User($db);

$method = $_SERVER['REQUEST_METHOD'];
$action = $GLOBALS['action'] ?? '';
$body   = json_decode(file_get_contents('php://input'), true) ?? [];

header('Content-Type: application/json');

switch ($action) {
    // ------------------------------------------------------------------ //
    //  POST /auth/login
    // ------------------------------------------------------------------ //
    case 'login':
        if ($method !== 'POST') { http_response_code(405); echo json_encode(['success'=>false,'message'=>'Method not allowed']); exit; }

        $identifier = trim($body['email'] ?? $body['username'] ?? '');
        $password   = $body['password'] ?? '';

        if (empty($identifier) || empty($password)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Email/username and password are required.']);
            exit;
        }

        $found = filter_var($identifier, FILTER_VALIDATE_EMAIL)
            ? $user->getByEmail($identifier)
            : $user->getByUsername($identifier);

        if (!$found || !$user->verifyPassword($found, $password)) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Invalid credentials.']);
            exit;
        }

        if (!$found['is_active']) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Account is disabled.']);
            exit;
        }

        $token = $auth->createToken((int) $found['id']);
        echo json_encode([
            'success' => true,
            'message' => 'Login successful.',
            'token'   => $token,
            'user'    => [
                'id'       => $found['id'],
                'username' => $found['username'],
                'email'    => $found['email'],
                'role'     => $found['role'],
            ],
        ]);
        break;

    // ------------------------------------------------------------------ //
    //  POST /auth/logout
    // ------------------------------------------------------------------ //
    case 'logout':
        if ($method !== 'POST') { http_response_code(405); echo json_encode(['success'=>false,'message'=>'Method not allowed']); exit; }

        $token = $auth->getBearerToken();
        if ($token) {
            $auth->revokeToken($token);
        }
        echo json_encode(['success' => true, 'message' => 'Logged out successfully.']);
        break;

    // ------------------------------------------------------------------ //
    //  POST /auth/register
    // ------------------------------------------------------------------ //
    case 'register':
        if ($method !== 'POST') { http_response_code(405); echo json_encode(['success'=>false,'message'=>'Method not allowed']); exit; }

        $required = ['username', 'email', 'password'];
        foreach ($required as $field) {
            if (empty($body[$field])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => "Field '{$field}' is required."]);
                exit;
            }
        }

        try {
            // New registrations are always 'user' role; admins are created via admin panel
            $body['role'] = 'user';
            $newId = $user->create($body);
            $token = $auth->createToken($newId);
            $newUser = $user->getById($newId);

            http_response_code(201);
            echo json_encode([
                'success' => true,
                'message' => 'Registration successful.',
                'token'   => $token,
                'user'    => [
                    'id'       => $newUser['id'],
                    'username' => $newUser['username'],
                    'email'    => $newUser['email'],
                    'role'     => $newUser['role'],
                ],
            ]);
        } catch (InvalidArgumentException $e) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    default:
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Endpoint not found.']);
}
