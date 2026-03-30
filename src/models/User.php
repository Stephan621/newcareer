<?php
/**
 * User model.
 */

require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/config/config.php';

class User
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /** Find a user by primary key. */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, username, email, role, created_at, updated_at FROM users WHERE id = ?'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Find a user by e-mail address. */
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, username, email, password_hash, role, created_at, updated_at FROM users WHERE email = ?'
        );
        $stmt->execute([strtolower(trim($email))]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Create a new user.
     *
     * @return int  New user ID on success, 0 on failure.
     */
    public function create(string $username, string $email, string $password, string $role = 'user'): int
    {
        $hash = password_hash($password, PASSWORD_ALGO, PASSWORD_OPTIONS);
        $stmt = $this->db->prepare(
            'INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([
            trim($username),
            strtolower(trim($email)),
            $hash,
            $role,
        ]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Update a user's profile.
     * Pass only the fields that should be changed; password is optional.
     */
    public function update(int $id, array $data): bool
    {
        $allowed = ['username', 'email', 'role'];
        $sets    = [];
        $params  = [];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $sets[]   = "`$field` = ?";
                $params[] = $data[$field];
            }
        }

        if (!empty($data['password'])) {
            $sets[]   = '`password_hash` = ?';
            $params[] = password_hash($data['password'], PASSWORD_ALGO, PASSWORD_OPTIONS);
        }

        if (empty($sets)) {
            return false;
        }

        $params[] = $id;
        $sql      = 'UPDATE users SET ' . implode(', ', $sets) . ' WHERE id = ?';
        $stmt     = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /** Delete a user by ID. */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM users WHERE id = ?');
        return $stmt->execute([$id]);
    }

    /**
     * Verify credentials.
     *
     * @return array|null  Full user row (without password_hash) on success, null on failure.
     */
    public function authenticate(string $email, string $password): ?array
    {
        $user = $this->findByEmail($email);
        if ($user === null) {
            return null;
        }
        if (!password_verify($password, $user['password_hash'])) {
            return null;
        }
        unset($user['password_hash']);
        return $user;
    }

    /** Return all users (without password hashes). */
    public function getAllUsers(): array
    {
        $stmt = $this->db->query(
            'SELECT id, username, email, role, created_at, updated_at FROM users ORDER BY created_at DESC'
        );
        return $stmt->fetchAll();
    }

    /** Check if an e-mail is already registered. */
    public function emailExists(string $email): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM users WHERE email = ?');
        $stmt->execute([strtolower(trim($email))]);
        return (bool) $stmt->fetchColumn();
    }

    /** Check if a username is already taken. */
    public function usernameExists(string $username): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM users WHERE username = ?');
        $stmt->execute([trim($username)]);
        return (bool) $stmt->fetchColumn();
    }

    /** Return the total number of registered users. */
    public function countAll(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM users')->fetchColumn();
    }
}
