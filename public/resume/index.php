<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/src/helpers/functions.php';
require_once dirname(__DIR__, 2) . '/src/helpers/auth.php';
require_once dirname(__DIR__, 2) . '/src/models/Resume.php';

requireLogin();

$user        = getCurrentUser();
$resumeModel = new Resume();
$resumes     = $resumeModel->findByUserId($user['id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Resumes – <?= APP_NAME ?></title>
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
                <li><a href="../applications/index.php">Applications</a></li>
                <?php if ($user['role'] === 'admin'): ?>
                    <li><a href="../../admin/index.php">Admin</a></li>
                <?php endif; ?>
                <li><a href="../logout.php" class="btn btn-outline btn-sm">Logout</a></li>
            </ul>
        </div>
    </nav>

    <main class="container section">
        <?php renderFlash(); ?>

        <div class="page-header">
            <h1>My Resumes</h1>
            <a href="create.php" class="btn btn-primary">+ New Resume</a>
        </div>

        <?php if (empty($resumes)): ?>
            <div class="empty-state card">
                <div class="empty-icon">📄</div>
                <h3>No resumes yet</h3>
                <p>Create your first resume to start applying for jobs.</p>
                <a href="create.php" class="btn btn-primary">Create Resume</a>
            </div>
        <?php else: ?>
            <div class="resume-grid">
                <?php foreach ($resumes as $resume): ?>
                    <div class="card resume-card">
                        <div class="card-body">
                            <h3><?= htmlspecialchars($resume['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                            <?php if ($resume['summary']): ?>
                                <p class="text-muted"><?= truncate($resume['summary'], 100) ?></p>
                            <?php endif; ?>
                            <?php if ($resume['skills']): ?>
                                <p class="resume-skills">
                                    <strong>Skills:</strong> <?= truncate($resume['skills'], 80) ?>
                                </p>
                            <?php endif; ?>
                            <small class="text-muted">Last updated: <?= formatDate($resume['updated_at'], DATETIME_FORMAT) ?></small>
                        </div>
                        <div class="card-footer">
                            <a href="edit.php?id=<?= $resume['id'] ?>" class="btn btn-outline btn-sm">Edit</a>
                            <form method="POST" action="delete.php" style="display:inline"
                                  onsubmit="return confirm('Delete this resume? This cannot be undone.')">
                                <?= csrfField() ?>
                                <input type="hidden" name="id" value="<?= $resume['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <footer class="footer">
        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> <?= APP_NAME ?>. All rights reserved.</p>
        </div>
    </footer>

    <script src="../../assets/js/main.js"></script>
</body>
</html>
