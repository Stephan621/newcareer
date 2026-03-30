<?php
require_once __DIR__ . '/../config/database.php';

class Application {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function findById(int $id): array|false {
        $stmt = $this->db->prepare('SELECT * FROM applications WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function findByUser(int $userId): array {
        $stmt = $this->db->prepare(
            'SELECT a.*, j.title AS job_title, j.company, r.title AS resume_title
             FROM applications a
             JOIN jobs j ON a.job_id = j.id
             JOIN resumes r ON a.resume_id = r.id
             WHERE a.user_id = ?
             ORDER BY a.applied_at DESC'
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function all(): array {
        $stmt = $this->db->query(
            'SELECT a.*, u.name AS user_name, u.email AS user_email,
                    j.title AS job_title, j.company, r.title AS resume_title
             FROM applications a
             JOIN users u ON a.user_id = u.id
             JOIN jobs j ON a.job_id = j.id
             JOIN resumes r ON a.resume_id = r.id
             ORDER BY a.applied_at DESC'
        );
        return $stmt->fetchAll();
    }

    public function create(int $userId, int $jobId, int $resumeId, string $coverLetter = ''): int {
        $stmt = $this->db->prepare(
            'INSERT INTO applications (user_id, job_id, resume_id, cover_letter) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$userId, $jobId, $resumeId, $coverLetter]);
        return (int) $this->db->lastInsertId();
    }

    public function updateStatus(int $id, string $status): void {
        $stmt = $this->db->prepare('UPDATE applications SET status = ? WHERE id = ?');
        $stmt->execute([$status, $id]);
    }

    public function hasApplied(int $userId, int $jobId): bool {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM applications WHERE user_id = ? AND job_id = ?');
        $stmt->execute([$userId, $jobId]);
        return (int) $stmt->fetchColumn() > 0;
    }
}
