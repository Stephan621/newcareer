<?php
require_once __DIR__ . '/../config/database.php';

class Job {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function findById(int $id): array|false {
        $stmt = $this->db->prepare('SELECT * FROM jobs WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function all(bool $activeOnly = true): array {
        $sql = 'SELECT * FROM jobs';
        if ($activeOnly) {
            $sql .= ' WHERE is_active = 1';
        }
        $sql .= ' ORDER BY created_at DESC';
        return $this->db->query($sql)->fetchAll();
    }

    public function create(array $data): int {
        $stmt = $this->db->prepare(
            'INSERT INTO jobs (title, company, location, description, requirements, salary_range)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['title'],
            $data['company'],
            $data['location'] ?? null,
            $data['description'],
            $data['requirements'] ?? null,
            $data['salary_range'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): void {
        $stmt = $this->db->prepare(
            'UPDATE jobs SET title = ?, company = ?, location = ?, description = ?,
             requirements = ?, salary_range = ?, is_active = ? WHERE id = ?'
        );
        $stmt->execute([
            $data['title'],
            $data['company'],
            $data['location'] ?? null,
            $data['description'],
            $data['requirements'] ?? null,
            $data['salary_range'] ?? null,
            (int) ($data['is_active'] ?? 1),
            $id,
        ]);
    }

    public function delete(int $id): void {
        $stmt = $this->db->prepare('DELETE FROM jobs WHERE id = ?');
        $stmt->execute([$id]);
    }
}
