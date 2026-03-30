<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/src/helpers/functions.php';
require_once dirname(__DIR__, 2) . '/src/helpers/auth.php';
require_once dirname(__DIR__, 2) . '/src/models/Application.php';

requireLogin();

$user         = getCurrentUser();
$appModel     = new Application();
$applications = $appModel->findByUserId($user['id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Applications – <?= APP_NAME ?></title>
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
                <li><a href="../resume/index.php">Resumes</a></li>
                <li><a href="index.php" class="active">Applications</a></li>
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
            <h1>My Applications</h1>
            <a href="../jobs/index.php" class="btn btn-primary">Browse More Jobs</a>
        </div>

        <?php if (empty($applications)): ?>
            <div class="empty-state card">
                <div class="empty-icon">📭</div>
                <h3>No applications yet</h3>
                <p>Start applying to jobs to track your progress here.</p>
                <a href="../jobs/index.php" class="btn btn-primary">Browse Jobs</a>
            </div>
        <?php else: ?>
            <div class="table-wrapper card">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Job Title</th>
                            <th>Company</th>
                            <th>Location</th>
                            <th>Type</th>
                            <th>Resume</th>
                            <th>Status</th>
                            <th>Applied</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($applications as $app): ?>
                            <tr>
                                <td>
                                    <a href="../jobs/view.php?id=<?= $app['job_id'] ?>">
                                        <?= htmlspecialchars($app['job_title'], ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                </td>
                                <td><?= htmlspecialchars($app['company'],  ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($app['location'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= statusBadge($app['job_type']) ?></td>
                                <td>
                                    <?= $app['resume_title']
                                        ? htmlspecialchars($app['resume_title'], ENT_QUOTES, 'UTF-8')
                                        : '<span class="text-muted">None</span>' ?>
                                </td>
                                <td><?= statusBadge($app['status']) ?></td>
                                <td><?= formatDate($app['created_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
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
