<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/src/helpers/functions.php';
require_once dirname(__DIR__, 2) . '/src/helpers/auth.php';
require_once dirname(__DIR__, 2) . '/src/models/Resume.php';

requireLogin();

$user        = getCurrentUser();
$resumeModel = new Resume();
$errors      = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        $title      = sanitize($_POST['title']      ?? '');
        $summary    = sanitize($_POST['summary']    ?? '');
        $skills     = sanitize($_POST['skills']     ?? '');
        $experience = sanitize($_POST['experience'] ?? '');
        $education  = sanitize($_POST['education']  ?? '');

        if ($title === '') {
            $errors[] = 'Resume title is required.';
        }

        if (empty($errors)) {
            $id = $resumeModel->create($user['id'], $title, $summary, $skills, $experience, $education);
            flashMessage('success', 'Resume created successfully!');
            redirect('index.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Resume – <?= APP_NAME ?></title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container nav-container">
            <a href="../index.php" class="nav-logo"><?= APP_NAME ?></a>
            <button class="nav-toggle" id="navToggle" aria-label="Toggle navigation">&#9776;</button>
            <ul class="nav-links" id="navLinks">
                <li><a href="../index.php">Home</a></li>
                <li><a href="../jobs/index.php">Jobs</a></li>
                <li><a href="../dashboard.php">Dashboard</a></li>
                <li><a href="index.php" class="active">Resumes</a></li>
                <li><a href="../logout.php" class="btn btn-outline btn-sm">Logout</a></li>
            </ul>
        </div>
    </nav>

    <main class="container section">
        <div class="page-header">
            <h1>Create New Resume</h1>
            <a href="index.php" class="btn btn-outline">&larr; Back to Resumes</a>
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
                <form method="POST" action="create.php" class="form" id="resumeForm">
                    <?= csrfField() ?>

                    <div class="form-group">
                        <label for="title" class="form-label">Resume Title <span class="required">*</span></label>
                        <input
                            type="text"
                            id="title"
                            name="title"
                            class="form-control"
                            value="<?= htmlspecialchars($_POST['title'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            required
                            placeholder="e.g., Senior PHP Developer Resume"
                        >
                    </div>

                    <div class="form-group">
                        <label for="summary" class="form-label">Professional Summary</label>
                        <textarea
                            id="summary"
                            name="summary"
                            class="form-control"
                            rows="4"
                            placeholder="A brief overview of your professional background and career goals…"
                        ><?= htmlspecialchars($_POST['summary'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="skills" class="form-label">Skills</label>
                        <textarea
                            id="skills"
                            name="skills"
                            class="form-control"
                            rows="3"
                            placeholder="PHP, MySQL, JavaScript, React, Docker, Git…"
                        ><?= htmlspecialchars($_POST['skills'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                        <small class="form-hint">List your key skills, separated by commas.</small>
                    </div>

                    <div class="form-group">
                        <label for="experience" class="form-label">Work Experience</label>
                        <textarea
                            id="experience"
                            name="experience"
                            class="form-control"
                            rows="6"
                            placeholder="Company Name – Job Title (Month Year – Month Year)&#10;• Key responsibility&#10;• Achievement"
                        ><?= htmlspecialchars($_POST['experience'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="education" class="form-label">Education</label>
                        <textarea
                            id="education"
                            name="education"
                            class="form-control"
                            rows="4"
                            placeholder="University / College – Degree (Year)&#10;e.g., State University – B.Sc. Computer Science (2018)"
                        ><?= htmlspecialchars($_POST['education'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Save Resume</button>
                        <a href="index.php" class="btn btn-outline">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> <?= APP_NAME ?>. All rights reserved.</p>
        </div>
    </footer>

    <script src="../../assets/js/main.js"></script>
</body>
</html>
