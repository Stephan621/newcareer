<?php
/**
 * Application model.
 */

require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/config/config.php';

class Application
{
    private PDO $db;

    public const STATUSES = ['pending', 'reviewed', 'accepted', 'rejected'];

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /** Find an application by primary key with job/user/resume details. */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT a.*,
                    u.username, u.email,
                    j.title AS job_title, j.company, j.location, j.job_type,
                    r.title AS resume_title
             FROM applications a
             JOIN users   u ON u.id = a.user_id
             JOIN jobs    j ON j.id = a.job_id
             LEFT JOIN resumes r ON r.id = a.resume_id
             WHERE a.id = ?'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Return all applications submitted by a specific user. */
    public function findByUserId(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT a.*,
                    j.title AS job_title, j.company, j.location, j.job_type,
                    r.title AS resume_title
             FROM applications a
             JOIN jobs    j ON j.id = a.job_id
             LEFT JOIN resumes r ON r.id = a.resume_id
             WHERE a.user_id = ?
             ORDER BY a.created_at DESC'
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /** Return all applications for a specific job. */
    public function findByJobId(int $jobId): array
    {
        $stmt = $this->db->prepare(
            'SELECT a.*,
                    u.username, u.email,
                    r.title AS resume_title
             FROM applications a
             JOIN users u ON u.id = a.user_id
             LEFT JOIN resumes r ON r.id = a.resume_id
             WHERE a.job_id = ?
             ORDER BY a.created_at DESC'
        );
        $stmt->execute([$jobId]);
        return $stmt->fetchAll();
    }

    /**
     * Submit a new application.
     *
     * @return int|false  New application ID on success, false if duplicate.
     */
    public function create(int $userId, int $jobId, ?int $resumeId, string $coverLetter = '')
    {
        // Prevent duplicate applications
        if ($this->hasApplied($userId, $jobId)) {
            return false;
        }

        $stmt = $this->db->prepare(
            'INSERT INTO applications (user_id, job_id, resume_id, cover_letter) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$userId, $jobId, $resumeId, $coverLetter]);
        return (int) $this->db->lastInsertId();
    }

    /** Update the status of an application. */
    public function update(int $id, string $status): bool
    {
        if (!in_array($status, self::STATUSES, true)) {
            return false;
        }
        $stmt = $this->db->prepare('UPDATE applications SET status = ? WHERE id = ?');
        return $stmt->execute([$status, $id]);
    }

    /** Delete an application. */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM applications WHERE id = ?');
        return $stmt->execute([$id]);
    }

    /** Return all applications with full details (admin view). */
    public function getAll(): array
    {
        $stmt = $this->db->query(
            'SELECT a.*,
                    u.username, u.email,
                    j.title AS job_title, j.company,
                    r.title AS resume_title
             FROM applications a
             JOIN users   u ON u.id = a.user_id
             JOIN jobs    j ON j.id = a.job_id
             LEFT JOIN resumes r ON r.id = a.resume_id
             ORDER BY a.created_at DESC'
        );
        return $stmt->fetchAll();
    }

    /** Check whether a user has already applied to a given job. */
    public function hasApplied(int $userId, int $jobId): bool
    {
        $stmt = $this->db->prepare(
            'SELECT 1 FROM applications WHERE user_id = ? AND job_id = ?'
        );
        $stmt->execute([$userId, $jobId]);
        return (bool) $stmt->fetchColumn();
    }

    /** Return total number of applications. */
    public function countAll(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM applications')->fetchColumn();
    }

    /** Return counts grouped by status. */
    public function countByStatus(): array
    {
        $stmt = $this->db->query(
            'SELECT status, COUNT(*) AS total FROM applications GROUP BY status'
        );
        $rows = $stmt->fetchAll();
        $map  = [];
        foreach ($rows as $row) {
            $map[$row['status']] = (int) $row['total'];
        }
        return $map;
    }
}
