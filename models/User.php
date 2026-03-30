<?php
/**
 * User model — handles user/admin management
 */
class User {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function create(array $data): int {
        $this->validateEmail($data['email']);
        $this->validateUsername($data['username']);
        $this->validatePassword($data['password']);

        if ($this->emailExists($data['email'])) {
            throw new InvalidArgumentException('Email already in use.');
        }
        if ($this->usernameExists($data['username'])) {
            throw new InvalidArgumentException('Username already taken.');
        }

        $hash = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);
        $role = in_array($data['role'] ?? '', ['admin', 'user']) ? $data['role'] : 'user';

        $stmt = $this->db->prepare(
            "INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([$data['username'], $data['email'], $hash, $role]);
        return (int) $this->db->lastInsertId();
    }

    public function getById(int $id): ?array {
        $stmt = $this->db->prepare(
            "SELECT id, username, email, role, is_active, created_at, updated_at FROM users WHERE id = ?"
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function getByEmail(string $email): ?array {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch() ?: null;
    }

    public function getByUsername(string $username): ?array {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetch() ?: null;
    }

    public function getAll(int $page = 1, int $perPage = 20): array {
        $offset = ($page - 1) * $perPage;
        $stmt = $this->db->prepare(
            "SELECT id, username, email, role, is_active, created_at, updated_at
             FROM users ORDER BY created_at DESC LIMIT ? OFFSET ?"
        );
        $stmt->execute([$perPage, $offset]);
        $users = $stmt->fetchAll();

        $countStmt = $this->db->query("SELECT COUNT(*) FROM users");
        $total = (int) $countStmt->fetchColumn();

        return ['data' => $users, 'total' => $total, 'page' => $page, 'per_page' => $perPage];
    }

    public function update(int $id, array $data): bool {
        $fields = [];
        $params = [];

        if (!empty($data['email'])) {
            $this->validateEmail($data['email']);
            $existing = $this->getByEmail($data['email']);
            if ($existing && (int)$existing['id'] !== $id) {
                throw new InvalidArgumentException('Email already in use.');
            }
            $fields[] = 'email = ?';
            $params[] = $data['email'];
        }

        if (!empty($data['username'])) {
            $this->validateUsername($data['username']);
            $existing = $this->getByUsername($data['username']);
            if ($existing && (int)$existing['id'] !== $id) {
                throw new InvalidArgumentException('Username already taken.');
            }
            $fields[] = 'username = ?';
            $params[] = $data['username'];
        }

        if (!empty($data['password'])) {
            $this->validatePassword($data['password']);
            $fields[] = 'password_hash = ?';
            $params[] = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);
        }

        if (isset($data['role']) && in_array($data['role'], ['admin', 'user'])) {
            $fields[] = 'role = ?';
            $params[] = $data['role'];
        }

        if (isset($data['is_active'])) {
            $fields[] = 'is_active = ?';
            $params[] = (int) $data['is_active'];
        }

        if (empty($fields)) return false;

        $params[] = $id;
        $stmt = $this->db->prepare("UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?");
        $stmt->execute($params);
        return $stmt->rowCount() > 0;
    }

    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    public function verifyPassword(array $user, string $password): bool {
        return password_verify($password, $user['password_hash']);
    }

    public function emailExists(string $email): bool {
        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return (bool) $stmt->fetch();
    }

    public function usernameExists(string $username): bool {
        $stmt = $this->db->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        return (bool) $stmt->fetch();
    }

    // ------------------------------------------------------------------ //
    //  Validation helpers
    // ------------------------------------------------------------------ //

    private function validateEmail(string $email): void {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid email address.');
        }
    }

    private function validateUsername(string $username): void {
        if (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username)) {
            throw new InvalidArgumentException(
                'Username must be 3-50 characters and contain only letters, numbers, and underscores.'
            );
        }
    }

    private function validatePassword(string $password): void {
        if (strlen($password) < 8) {
            throw new InvalidArgumentException('Password must be at least 8 characters long.');
        }
    }
}
