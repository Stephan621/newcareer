<?php
/**
 * Authentication middleware
 * Validates Bearer token from Authorization header
 */
class Auth {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    /**
     * Extract Bearer token from Authorization header
     */
    public function getBearerToken(): ?string {
        $headers = getallheaders();
        $auth = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        if (preg_match('/Bearer\s+(.+)/i', $auth, $matches)) {
            return trim($matches[1]);
        }
        return null;
    }

    /**
     * Validate token and return user array or null
     */
    public function authenticate(): ?array {
        $token = $this->getBearerToken();
        if ($token === null) {
            return null;
        }

        $stmt = $this->db->prepare(
            "SELECT u.id, u.username, u.email, u.role, u.is_active
             FROM auth_tokens t
             JOIN users u ON u.id = t.user_id
             WHERE t.token = ? AND t.expires_at > NOW() AND u.is_active = 1"
        );
        $stmt->execute([$token]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    /**
     * Require authentication; send 401 and exit on failure
     */
    public function requireAuth(): array {
        $user = $this->authenticate();
        if ($user === null) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized. Valid token required.']);
            exit;
        }
        return $user;
    }

    /**
     * Require admin role; send 403 and exit on failure
     */
    public function requireAdmin(): array {
        $user = $this->requireAuth();
        if ($user['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Forbidden. Admin access required.']);
            exit;
        }
        return $user;
    }

    /**
     * Generate a secure random token
     */
    public static function generateToken(): string {
        return bin2hex(random_bytes(32));
    }

    /**
     * Create and store a token for the given user
     */
    public function createToken(int $userId, int $ttlHours = 24): string {
        $token = self::generateToken();
        $expires = date('Y-m-d H:i:s', time() + $ttlHours * 3600);

        $stmt = $this->db->prepare(
            "INSERT INTO auth_tokens (user_id, token, expires_at) VALUES (?, ?, ?)"
        );
        $stmt->execute([$userId, $token, $expires]);
        return $token;
    }

    /**
     * Revoke a specific token
     */
    public function revokeToken(string $token): void {
        $stmt = $this->db->prepare("DELETE FROM auth_tokens WHERE token = ?");
        $stmt->execute([$token]);
    }

    /**
     * Revoke all tokens for a user
     */
    public function revokeAllUserTokens(int $userId): void {
        $stmt = $this->db->prepare("DELETE FROM auth_tokens WHERE user_id = ?");
        $stmt->execute([$userId]);
    }
}
