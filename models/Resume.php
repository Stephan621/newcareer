<?php
require_once __DIR__ . '/../config/database.php';

class Resume {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function findById(int $id): array|false {
        $stmt = $this->db->prepare('SELECT * FROM resumes WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function findByUser(int $userId): array {
        $stmt = $this->db->prepare('SELECT * FROM resumes WHERE user_id = ? ORDER BY created_at DESC');
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function create(int $userId, string $title, string $summary = ''): int {
        $stmt = $this->db->prepare('INSERT INTO resumes (user_id, title, summary) VALUES (?, ?, ?)');
        $stmt->execute([$userId, $title, $summary]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, string $title, string $summary): void {
        $stmt = $this->db->prepare('UPDATE resumes SET title = ?, summary = ? WHERE id = ?');
        $stmt->execute([$title, $summary, $id]);
    }

    public function delete(int $id): void {
        $stmt = $this->db->prepare('DELETE FROM resumes WHERE id = ?');
        $stmt->execute([$id]);
    }

    public function getEducation(int $resumeId): array {
        $stmt = $this->db->prepare('SELECT * FROM education WHERE resume_id = ? ORDER BY start_year DESC');
        $stmt->execute([$resumeId]);
        return $stmt->fetchAll();
    }

    public function addEducation(int $resumeId, array $data): int {
        $stmt = $this->db->prepare(
            'INSERT INTO education (resume_id, institution, degree, field_of_study, start_year, end_year, description)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $resumeId,
            $data['institution'],
            $data['degree'],
            $data['field_of_study'] ?? null,
            $data['start_year'] ?? null,
            $data['end_year'] ?? null,
            $data['description'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function getExperience(int $resumeId): array {
        $stmt = $this->db->prepare('SELECT * FROM experience WHERE resume_id = ? ORDER BY start_date DESC');
        $stmt->execute([$resumeId]);
        return $stmt->fetchAll();
    }

    public function addExperience(int $resumeId, array $data): int {
        $stmt = $this->db->prepare(
            'INSERT INTO experience (resume_id, company, position, start_date, end_date, is_current, description)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $resumeId,
            $data['company'],
            $data['position'],
            $data['start_date'] ?? null,
            $data['end_date'] ?? null,
            (int) ($data['is_current'] ?? 0),
            $data['description'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function getSkills(int $resumeId): array {
        $stmt = $this->db->prepare('SELECT * FROM skills WHERE resume_id = ?');
        $stmt->execute([$resumeId]);
        return $stmt->fetchAll();
    }

    public function addSkill(int $resumeId, string $name, string $level = 'intermediate'): int {
        $stmt = $this->db->prepare('INSERT INTO skills (resume_id, name, level) VALUES (?, ?, ?)');
        $stmt->execute([$resumeId, $name, $level]);
        return (int) $this->db->lastInsertId();
    }
}
