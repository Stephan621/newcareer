<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/src/helpers/functions.php';
require_once dirname(__DIR__, 2) . '/src/helpers/auth.php';
require_once dirname(__DIR__, 2) . '/src/models/Job.php';
require_once dirname(__DIR__, 2) . '/src/models/Resume.php';
require_once dirname(__DIR__, 2) . '/src/models/Application.php';

requireLogin();

$user        = getCurrentUser();
$jobModel    = new Job();
$resumeModel = new Resume();
$appModel    = new Application();

$jobId = (int) ($_GET['id'] ?? 0);
$job   = $jobModel->findById($jobId);

if (!$job || $job['status'] !== 'active') {
    flashMessage('error', 'Job not found or no longer accepting applications.');
    redirect('index.php');
}

if ($appModel->hasApplied($user['id'], $jobId)) {
    flashMessage('info', 'You have already applied for this job.');
    redirect('view.php?id=' . $jobId);
}

$resumes = $resumeModel->findByUserId($user['id']);
$errors  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        $resumeId    = !empty($_POST['resume_id']) ? (int) $_POST['resume_id'] : null;
        $coverLetter = sanitize($_POST['cover_letter'] ?? '');

        // Validate resume ownership if selected
        if ($resumeId !== null) {
            $selectedResume = $resumeModel->findById($resumeId);
            if (!$selectedResume || (int) $selectedResume['user_id'] !== $user['id']) {
                $errors[] = 'Selected resume is invalid.';
                $resumeId = null;
            }
        }

        if (empty($errors)) {
            $result = $appModel->create($user['id'], $jobId, $resumeId, $coverLetter);

            if ($result === false) {
                $errors[] = 'You have already applied for this job.';
            } else {
                flashMessage('success', 'Application submitted successfully! Good luck!');
                redirect('../applications/index.php');
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply – <?= htmlspecialchars($job['title'], ENT_QUOTES, 'UTF-8') ?> – <?= APP_NAME ?></title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container nav-container">
            <a href="../index.php" class="nav-logo"><?= APP_NAME ?></a>
            <button class="nav-toggle" id="navToggle" aria-label="Toggle navigation">&#9776;</button>
            <ul class="nav-links" id="navLinks">
                <li><a href="../index.php">Home</a></li>
                <li><a href="index.php">Jobs</a></li>
                <li><a href="../dashboard.php">Dashboard</a></li>
                <li><a href="../logout.php" class="btn btn-outline btn-sm">Logout</a></li>
            </ul>
        </div>
    </nav>

    <main class="container section">
        <a href="view.php?id=<?= $jobId ?>" class="back-link">&larr; Back to Job</a>

        <div class="page-header">
            <div>
                <h1>Apply for Position</h1>
                <p class="text-muted">
                    <?= htmlspecialchars($job['title'], ENT_QUOTES, 'UTF-8') ?> at
                    <?= htmlspecialchars($job['company'], ENT_QUOTES, 'UTF-8') ?>
                </p>
            </div>
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
                <form method="POST" action="apply.php?id=<?= $jobId ?>" class="form">
                    <?= csrfField() ?>

                    <div class="form-group">
                        <label for="resume_id" class="form-label">Select Resume</label>
                        <?php if (empty($resumes)): ?>
                            <div class="alert alert-warning">
                                You don't have any resumes yet.
                                <a href="../resume/create.php">Create one now</a> before applying.
                            </div>
                        <?php else: ?>
                            <select id="resume_id" name="resume_id" class="form-control">
                                <option value="">— No resume (apply without resume) —</option>
                                <?php foreach ($resumes as $resume): ?>
                                    <option value="<?= $resume['id'] ?>"
                                        <?= (int) ($_POST['resume_id'] ?? 0) === (int) $resume['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($resume['title'], ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-hint">
                                Or <a href="../resume/create.php">create a new resume</a>.
                            </small>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="cover_letter" class="form-label">Cover Letter</label>
                        <textarea
                            id="cover_letter"
                            name="cover_letter"
                            class="form-control"
                            rows="8"
                            placeholder="Introduce yourself and explain why you're a great fit for this role…"
                        ><?= htmlspecialchars($_POST['cover_letter'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                        <small class="form-hint">Optional but strongly recommended.</small>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary" <?= empty($resumes) ? '' : '' ?>>
                            Submit Application
                        </button>
                        <a href="view.php?id=<?= $jobId ?>" class="btn btn-outline">Cancel</a>
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
