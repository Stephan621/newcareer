<?php
/**
 * Resume model.
 */

require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/config/config.php';

class Resume
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /** Find a resume by primary key. */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM resumes WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Return all resumes that belong to a given user. */
    public function findByUserId(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM resumes WHERE user_id = ? ORDER BY created_at DESC'
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /**
     * Create a new resume.
     *
     * @return int  New resume ID.
     */
    public function create(int $userId, string $title, string $summary = '', string $skills = '', string $experience = '', string $education = ''): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO resumes (user_id, title, summary, skills, experience, education)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$userId, $title, $summary, $skills, $experience, $education]);
        return (int) $this->db->lastInsertId();
    }

    /** Update an existing resume. */
    public function update(int $id, array $data): bool
    {
        $allowed = ['title', 'summary', 'skills', 'experience', 'education'];
        $sets    = [];
        $params  = [];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $sets[]   = "`$field` = ?";
                $params[] = $data[$field];
            }
        }

        if (empty($sets)) {
            return false;
        }

        $params[] = $id;
        $stmt     = $this->db->prepare('UPDATE resumes SET ' . implode(', ', $sets) . ' WHERE id = ?');
        return $stmt->execute($params);
    }

    /** Delete a resume by ID. */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM resumes WHERE id = ?');
        return $stmt->execute([$id]);
    }

    /** Return all resumes joined with owner username. */
    public function getAllResumes(): array
    {
        $stmt = $this->db->query(
            'SELECT r.*, u.username, u.email
             FROM resumes r
             JOIN users u ON u.id = r.user_id
             ORDER BY r.created_at DESC'
        );
        return $stmt->fetchAll();
    }

    /** Return the total number of resumes. */
    public function countAll(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM resumes')->fetchColumn();
    }

    /** Return the number of resumes owned by a user. */
    public function countByUser(int $userId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM resumes WHERE user_id = ?');
        $stmt->execute([$userId]);
        return (int) $stmt->fetchColumn();
    }
}
