<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/src/helpers/functions.php';
require_once dirname(__DIR__, 2) . '/src/helpers/auth.php';
require_once dirname(__DIR__, 2) . '/src/models/Job.php';

requireAdmin();

$jobModel = new Job();
$errors   = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        $data = [
            'title'        => sanitize($_POST['title']        ?? ''),
            'company'      => sanitize($_POST['company']      ?? ''),
            'location'     => sanitize($_POST['location']     ?? ''),
            'description'  => sanitize($_POST['description']  ?? ''),
            'requirements' => sanitize($_POST['requirements'] ?? ''),
            'salary_range' => sanitize($_POST['salary_range'] ?? ''),
            'job_type'     => sanitize($_POST['job_type']     ?? ''),
            'status'       => sanitize($_POST['status']       ?? ''),
        ];

        if ($data['title'] === '')    $errors[] = 'Job title is required.';
        if ($data['company'] === '')  $errors[] = 'Company name is required.';
        if ($data['location'] === '') $errors[] = 'Location is required.';
        if ($data['description'] === '') $errors[] = 'Job description is required.';
        if (!in_array($data['job_type'], Job::TYPES,    true)) $errors[] = 'Invalid job type.';
        if (!in_array($data['status'],   Job::STATUSES, true)) $errors[] = 'Invalid status.';

        if (empty($errors)) {
            $id = $jobModel->create($data);
            flashMessage('success', 'Job listing created successfully!');
            redirect('../jobs.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Job – <?= APP_NAME ?> Admin</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container nav-container">
            <a href="../../public/index.php" class="nav-logo"><?= APP_NAME ?> <span class="admin-badge">Admin</span></a>
            <button class="nav-toggle" id="navToggle" aria-label="Toggle navigation">&#9776;</button>
            <ul class="nav-links" id="navLinks">
                <li><a href="../index.php">Dashboard</a></li>
                <li><a href="../users.php">Users</a></li>
                <li><a href="../jobs.php" class="active">Jobs</a></li>
                <li><a href="../applications.php">Applications</a></li>
                <li><a href="../../public/logout.php" class="btn btn-outline btn-sm">Logout</a></li>
            </ul>
        </div>
    </nav>

    <main class="container section">
        <div class="page-header">
            <h1>Create New Job Listing</h1>
            <a href="../jobs.php" class="btn btn-outline">&larr; Back to Jobs</a>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <ul class="mb-0">
                    <?php foreach ($errors as $e): ?>
                        <li><?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8') ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="card form-card">
            <div class="card-body">
                <form method="POST" action="create.php" class="form">
                    <?= csrfField() ?>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="title" class="form-label">Job Title <span class="required">*</span></label>
                            <input type="text" id="title" name="title" class="form-control" required
                                value="<?= htmlspecialchars($_POST['title'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                placeholder="e.g., Senior PHP Developer">
                        </div>
                        <div class="form-group">
                            <label for="company" class="form-label">Company <span class="required">*</span></label>
                            <input type="text" id="company" name="company" class="form-control" required
                                value="<?= htmlspecialchars($_POST['company'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                placeholder="e.g., TechCorp Solutions">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="location" class="form-label">Location <span class="required">*</span></label>
                            <input type="text" id="location" name="location" class="form-control" required
                                value="<?= htmlspecialchars($_POST['location'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                placeholder="e.g., San Francisco, CA or Remote">
                        </div>
                        <div class="form-group">
                            <label for="salary_range" class="form-label">Salary Range</label>
                            <input type="text" id="salary_range" name="salary_range" class="form-control"
                                value="<?= htmlspecialchars($_POST['salary_range'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                placeholder="e.g., $80,000 - $100,000">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="job_type" class="form-label">Job Type <span class="required">*</span></label>
                            <select id="job_type" name="job_type" class="form-control" required>
                                <?php foreach (Job::TYPES as $type): ?>
                                    <option value="<?= $type ?>"
                                        <?= ($_POST['job_type'] ?? 'full-time') === $type ? 'selected' : '' ?>>
                                        <?= ucfirst($type) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="status" class="form-label">Status</label>
                            <select id="status" name="status" class="form-control">
                                <option value="active"   <?= ($_POST['status'] ?? 'active') === 'active'   ? 'selected' : '' ?>>Active</option>
                                <option value="inactive" <?= ($_POST['status'] ?? '')        === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="description" class="form-label">Job Description <span class="required">*</span></label>
                        <textarea id="description" name="description" class="form-control" rows="8" required
                            placeholder="Describe the role, responsibilities, and company culture…"><?= htmlspecialchars($_POST['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="requirements" class="form-label">Requirements</label>
                        <textarea id="requirements" name="requirements" class="form-control" rows="6"
                            placeholder="List required qualifications, experience, skills…"><?= htmlspecialchars($_POST['requirements'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Publish Job</button>
                        <a href="../jobs.php" class="btn btn-outline">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> <?= APP_NAME ?> Admin Panel.</p>
        </div>
    </footer>

    <script src="../../assets/js/main.js"></script>
</body>
</html>
