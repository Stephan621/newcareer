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

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    redirect('index.php');
}

$resume = $resumeModel->findById($id);
if (!$resume || (int) $resume['user_id'] !== $user['id']) {
    flashMessage('error', 'Resume not found or access denied.');
    redirect('index.php');
}

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
            $resumeModel->update($id, compact('title', 'summary', 'skills', 'experience', 'education'));
            flashMessage('success', 'Resume updated successfully!');
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
    <title>Edit Resume – <?= APP_NAME ?></title>
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
            <h1>Edit Resume</h1>
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
                <form method="POST" action="edit.php?id=<?= $id ?>" class="form" id="resumeForm">
                    <?= csrfField() ?>

                    <div class="form-group">
                        <label for="title" class="form-label">Resume Title <span class="required">*</span></label>
                        <input
                            type="text"
                            id="title"
                            name="title"
                            class="form-control"
                            value="<?= htmlspecialchars($_POST['title'] ?? $resume['title'], ENT_QUOTES, 'UTF-8') ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="summary" class="form-label">Professional Summary</label>
                        <textarea id="summary" name="summary" class="form-control" rows="4"><?= htmlspecialchars($_POST['summary'] ?? $resume['summary'], ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="skills" class="form-label">Skills</label>
                        <textarea id="skills" name="skills" class="form-control" rows="3"><?= htmlspecialchars($_POST['skills'] ?? $resume['skills'], ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="experience" class="form-label">Work Experience</label>
                        <textarea id="experience" name="experience" class="form-control" rows="6"><?= htmlspecialchars($_POST['experience'] ?? $resume['experience'], ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="education" class="form-label">Education</label>
                        <textarea id="education" name="education" class="form-control" rows="4"><?= htmlspecialchars($_POST['education'] ?? $resume['education'], ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Update Resume</button>
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
