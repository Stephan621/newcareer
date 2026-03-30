<?php
/**
 * Job model.
 */

require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/config/config.php';

class Job
{
    private PDO $db;

    public const TYPES    = ['full-time', 'part-time', 'contract', 'remote'];
    public const STATUSES = ['active', 'inactive'];

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /** Find a job by primary key. */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM jobs WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Return all jobs, newest first. */
    public function getAll(): array
    {
        return $this->db->query('SELECT * FROM jobs ORDER BY created_at DESC')->fetchAll();
    }

    /** Return only active jobs. */
    public function getActive(): array
    {
        $stmt = $this->db->prepare("SELECT * FROM jobs WHERE status = 'active' ORDER BY created_at DESC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Create a new job listing.
     *
     * @return int  New job ID.
     */
    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO jobs (title, company, location, description, requirements, salary_range, job_type, status)
             VALUES (:title, :company, :location, :description, :requirements, :salary_range, :job_type, :status)'
        );
        $stmt->execute([
            ':title'        => $data['title'],
            ':company'      => $data['company'],
            ':location'     => $data['location'],
            ':description'  => $data['description'],
            ':requirements' => $data['requirements'] ?? '',
            ':salary_range' => $data['salary_range'] ?? '',
            ':job_type'     => $data['job_type'] ?? 'full-time',
            ':status'       => $data['status'] ?? 'active',
        ]);
        return (int) $this->db->lastInsertId();
    }

    /** Update an existing job listing. */
    public function update(int $id, array $data): bool
    {
        $allowed = ['title', 'company', 'location', 'description', 'requirements', 'salary_range', 'job_type', 'status'];
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
        $stmt     = $this->db->prepare('UPDATE jobs SET ' . implode(', ', $sets) . ' WHERE id = ?');
        return $stmt->execute($params);
    }

    /** Delete a job listing. */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM jobs WHERE id = ?');
        return $stmt->execute([$id]);
    }

    /**
     * Search active jobs by keyword, location, and/or type.
     * All filters are optional.
     */
    public function search(string $keyword = '', string $location = '', string $jobType = ''): array
    {
        $conditions = ["status = 'active'"];
        $params     = [];

        if ($keyword !== '') {
            $conditions[] = '(title LIKE ? OR company LIKE ? OR description LIKE ?)';
            $like         = '%' . $keyword . '%';
            $params[]     = $like;
            $params[]     = $like;
            $params[]     = $like;
        }

        if ($location !== '') {
            $conditions[] = 'location LIKE ?';
            $params[]     = '%' . $location . '%';
        }

        if ($jobType !== '' && in_array($jobType, self::TYPES, true)) {
            $conditions[] = 'job_type = ?';
            $params[]     = $jobType;
        }

        $sql  = 'SELECT * FROM jobs WHERE ' . implode(' AND ', $conditions) . ' ORDER BY created_at DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Count all jobs. */
    public function countAll(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM jobs')->fetchColumn();
    }

    /** Count active jobs. */
    public function countActive(): int
    {
        return (int) $this->db->query("SELECT COUNT(*) FROM jobs WHERE status = 'active'")->fetchColumn();
    }

    /** Return a few featured (most-recent active) jobs. */
    public function getFeatured(int $limit = 6): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM jobs WHERE status = 'active' ORDER BY created_at DESC LIMIT ?"
        );
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
}
