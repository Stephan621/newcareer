<?php
/**
 * Resume model — handles all resume-related DB operations
 */
class Resume {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    // ------------------------------------------------------------------ //
    //  Core resume CRUD
    // ------------------------------------------------------------------ //

    public function create(int $userId, array $data): int {
        $stmt = $this->db->prepare(
            "INSERT INTO resumes
                (user_id, title, summary, first_name, last_name, email, phone,
                 address, city, state, country, postal_code,
                 linkedin_url, github_url, website_url, is_public)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $userId,
            $data['title']        ?? 'My Resume',
            $data['summary']      ?? null,
            $data['first_name'],
            $data['last_name'],
            $data['email'],
            $data['phone']        ?? null,
            $data['address']      ?? null,
            $data['city']         ?? null,
            $data['state']        ?? null,
            $data['country']      ?? null,
            $data['postal_code']  ?? null,
            $data['linkedin_url'] ?? null,
            $data['github_url']   ?? null,
            $data['website_url']  ?? null,
            isset($data['is_public']) ? (int) $data['is_public'] : 0,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function getById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM resumes WHERE id = ?");
        $stmt->execute([$id]);
        $resume = $stmt->fetch();
        if (!$resume) return null;
        return $this->attachRelations($resume);
    }

    public function getByUserId(int $userId): array {
        $stmt = $this->db->prepare("SELECT * FROM resumes WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$userId]);
        $resumes = $stmt->fetchAll();
        return array_map([$this, 'attachRelations'], $resumes);
    }

    public function getAll(int $page = 1, int $perPage = 20): array {
        $offset = ($page - 1) * $perPage;
        $stmt = $this->db->prepare(
            "SELECT r.*, u.username, u.email AS user_email
             FROM resumes r
             JOIN users u ON u.id = r.user_id
             ORDER BY r.created_at DESC
             LIMIT ? OFFSET ?"
        );
        $stmt->execute([$perPage, $offset]);
        $resumes = $stmt->fetchAll();

        $countStmt = $this->db->query("SELECT COUNT(*) FROM resumes");
        $total = (int) $countStmt->fetchColumn();

        return ['data' => $resumes, 'total' => $total, 'page' => $page, 'per_page' => $perPage];
    }

    public function update(int $id, array $data): bool {
        $fields = [];
        $params = [];
        $allowed = ['title','summary','first_name','last_name','email','phone',
                    'address','city','state','country','postal_code',
                    'linkedin_url','github_url','website_url','is_public'];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "{$field} = ?";
                $params[] = $field === 'is_public' ? (int) $data[$field] : $data[$field];
            }
        }

        if (empty($fields)) return false;

        $params[] = $id;
        $sql = "UPDATE resumes SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount() > 0;
    }

    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM resumes WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    public function belongsToUser(int $id, int $userId): bool {
        $stmt = $this->db->prepare("SELECT id FROM resumes WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $userId]);
        return (bool) $stmt->fetch();
    }

    // ------------------------------------------------------------------ //
    //  Education
    // ------------------------------------------------------------------ //

    public function addEducation(int $resumeId, array $data): int {
        $stmt = $this->db->prepare(
            "INSERT INTO education
                (resume_id, institution, degree, field_of_study, start_date, end_date, is_current, description, gpa)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $resumeId,
            $data['institution'],
            $data['degree']         ?? null,
            $data['field_of_study'] ?? null,
            $data['start_date']     ?? null,
            $data['end_date']       ?? null,
            isset($data['is_current']) ? (int) $data['is_current'] : 0,
            $data['description']    ?? null,
            $data['gpa']            ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function updateEducation(int $id, array $data): bool {
        $fields = [];
        $params = [];
        $allowed = ['institution','degree','field_of_study','start_date','end_date','is_current','description','gpa'];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "{$field} = ?";
                $params[] = $field === 'is_current' ? (int) $data[$field] : $data[$field];
            }
        }
        if (empty($fields)) return false;

        $params[] = $id;
        $stmt = $this->db->prepare("UPDATE education SET " . implode(', ', $fields) . " WHERE id = ?");
        $stmt->execute($params);
        return $stmt->rowCount() > 0;
    }

    public function deleteEducation(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM education WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    // ------------------------------------------------------------------ //
    //  Experience
    // ------------------------------------------------------------------ //

    public function addExperience(int $resumeId, array $data): int {
        $stmt = $this->db->prepare(
            "INSERT INTO experience
                (resume_id, company, position, location, start_date, end_date, is_current, description)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $resumeId,
            $data['company'],
            $data['position'],
            $data['location']    ?? null,
            $data['start_date']  ?? null,
            $data['end_date']    ?? null,
            isset($data['is_current']) ? (int) $data['is_current'] : 0,
            $data['description'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function updateExperience(int $id, array $data): bool {
        $fields = [];
        $params = [];
        $allowed = ['company','position','location','start_date','end_date','is_current','description'];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "{$field} = ?";
                $params[] = $field === 'is_current' ? (int) $data[$field] : $data[$field];
            }
        }
        if (empty($fields)) return false;

        $params[] = $id;
        $stmt = $this->db->prepare("UPDATE experience SET " . implode(', ', $fields) . " WHERE id = ?");
        $stmt->execute($params);
        return $stmt->rowCount() > 0;
    }

    public function deleteExperience(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM experience WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    // ------------------------------------------------------------------ //
    //  Skills
    // ------------------------------------------------------------------ //

    public function addSkill(int $resumeId, array $data): int {
        $stmt = $this->db->prepare(
            "INSERT INTO skills (resume_id, name, proficiency, category) VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([
            $resumeId,
            $data['name'],
            $data['proficiency'] ?? 'intermediate',
            $data['category']    ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function updateSkill(int $id, array $data): bool {
        $fields = [];
        $params = [];
        $allowed = ['name','proficiency','category'];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "{$field} = ?";
                $params[] = $data[$field];
            }
        }
        if (empty($fields)) return false;

        $params[] = $id;
        $stmt = $this->db->prepare("UPDATE skills SET " . implode(', ', $fields) . " WHERE id = ?");
        $stmt->execute($params);
        return $stmt->rowCount() > 0;
    }

    public function deleteSkill(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM skills WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    // ------------------------------------------------------------------ //
    //  Projects
    // ------------------------------------------------------------------ //

    public function addProject(int $resumeId, array $data): int {
        $stmt = $this->db->prepare(
            "INSERT INTO projects (resume_id, name, description, url, start_date, end_date)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $resumeId,
            $data['name'],
            $data['description'] ?? null,
            $data['url']         ?? null,
            $data['start_date']  ?? null,
            $data['end_date']    ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function updateProject(int $id, array $data): bool {
        $fields = [];
        $params = [];
        $allowed = ['name','description','url','start_date','end_date'];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "{$field} = ?";
                $params[] = $data[$field];
            }
        }
        if (empty($fields)) return false;

        $params[] = $id;
        $stmt = $this->db->prepare("UPDATE projects SET " . implode(', ', $fields) . " WHERE id = ?");
        $stmt->execute($params);
        return $stmt->rowCount() > 0;
    }

    public function deleteProject(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM projects WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    // ------------------------------------------------------------------ //
    //  Certifications
    // ------------------------------------------------------------------ //

    public function addCertification(int $resumeId, array $data): int {
        $stmt = $this->db->prepare(
            "INSERT INTO certifications
                (resume_id, name, issuer, issue_date, expiry_date, credential_id, credential_url)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $resumeId,
            $data['name'],
            $data['issuer']         ?? null,
            $data['issue_date']     ?? null,
            $data['expiry_date']    ?? null,
            $data['credential_id']  ?? null,
            $data['credential_url'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function updateCertification(int $id, array $data): bool {
        $fields = [];
        $params = [];
        $allowed = ['name','issuer','issue_date','expiry_date','credential_id','credential_url'];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "{$field} = ?";
                $params[] = $data[$field];
            }
        }
        if (empty($fields)) return false;

        $params[] = $id;
        $stmt = $this->db->prepare("UPDATE certifications SET " . implode(', ', $fields) . " WHERE id = ?");
        $stmt->execute($params);
        return $stmt->rowCount() > 0;
    }

    public function deleteCertification(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM certifications WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    // ------------------------------------------------------------------ //
    //  Helper
    // ------------------------------------------------------------------ //

    private function attachRelations(array $resume): array {
        $id = (int) $resume['id'];

        $resume['education'] = $this->fetchAll(
            "SELECT * FROM education WHERE resume_id = ? ORDER BY start_date DESC", [$id]
        );

        $resume['experience'] = $this->fetchAll(
            "SELECT * FROM experience WHERE resume_id = ? ORDER BY start_date DESC", [$id]
        );
        $resume['skills'] = $this->fetchAll(
            "SELECT * FROM skills WHERE resume_id = ? ORDER BY name", [$id]
        );
        $resume['projects'] = $this->fetchAll(
            "SELECT * FROM projects WHERE resume_id = ? ORDER BY start_date DESC", [$id]
        );
        $resume['certifications'] = $this->fetchAll(
            "SELECT * FROM certifications WHERE resume_id = ? ORDER BY issue_date DESC", [$id]
        );

        return $resume;
    }

    private function fetchAll(string $sql, array $params = []): array {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
