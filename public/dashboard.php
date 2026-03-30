<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/src/helpers/functions.php';
require_once dirname(__DIR__) . '/src/helpers/auth.php';
require_once dirname(__DIR__) . '/src/models/Resume.php';
require_once dirname(__DIR__) . '/src/models/Application.php';
require_once dirname(__DIR__) . '/src/models/Job.php';

requireLogin();

$user        = getCurrentUser();
$resumeModel = new Resume();
$appModel    = new Application();
$jobModel    = new Job();

$resumes      = $resumeModel->findByUserId($user['id']);
$applications = $appModel->findByUserId($user['id']);
$activeJobs   = $jobModel->countActive();

// Quick stats
$totalResumes      = count($resumes);
$totalApplications = count($applications);
$pendingCount      = 0;
$acceptedCount     = 0;

foreach ($applications as $app) {
    if ($app['status'] === 'pending')  $pendingCount++;
    if ($app['status'] === 'accepted') $acceptedCount++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard – <?= APP_NAME ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container nav-container">
            <a href="index.php" class="nav-logo"><?= APP_NAME ?></a>
            <button class="nav-toggle" id="navToggle" aria-label="Toggle navigation">&#9776;</button>
            <ul class="nav-links" id="navLinks">
                <li><a href="index.php">Home</a></li>
                <li><a href="jobs/index.php">Jobs</a></li>
                <li><a href="dashboard.php" class="active">Dashboard</a></li>
                <li><a href="resume/index.php">Resumes</a></li>
                <li><a href="applications/index.php">Applications</a></li>
                <?php if ($user['role'] === 'admin'): ?>
                    <li><a href="../admin/index.php">Admin</a></li>
                <?php endif; ?>
                <li><a href="logout.php" class="btn btn-outline btn-sm">Logout</a></li>
            </ul>
        </div>
    </nav>

    <main class="container section">
        <?php renderFlash(); ?>

        <div class="page-header">
            <div>
                <h1>Welcome back, <?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') ?>!</h1>
                <p class="text-muted">Here's your career overview at a glance.</p>
            </div>
            <div class="page-header-actions">
                <a href="resume/create.php" class="btn btn-primary">+ New Resume</a>
                <a href="jobs/index.php" class="btn btn-outline">Browse Jobs</a>
            </div>
        </div>

        <!-- Stats cards -->
        <div class="stats-cards">
            <div class="stat-card">
                <div class="stat-card-icon icon-blue">📄</div>
                <div>
                    <div class="stat-card-value"><?= $totalResumes ?></div>
                    <div class="stat-card-label">Resumes</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon icon-green">📨</div>
                <div>
                    <div class="stat-card-value"><?= $totalApplications ?></div>
                    <div class="stat-card-label">Applications</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon icon-yellow">⏳</div>
                <div>
                    <div class="stat-card-value"><?= $pendingCount ?></div>
                    <div class="stat-card-label">Pending</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon icon-teal">✅</div>
                <div>
                    <div class="stat-card-value"><?= $acceptedCount ?></div>
                    <div class="stat-card-label">Accepted</div>
                </div>
            </div>
        </div>

        <div class="dashboard-grid">
            <!-- Resumes panel -->
            <div class="dashboard-panel">
                <div class="panel-header">
                    <h2>My Resumes</h2>
                    <a href="resume/index.php" class="btn btn-outline btn-sm">Manage</a>
                </div>
                <?php if (empty($resumes)): ?>
                    <div class="empty-state">
                        <p>You haven't created a resume yet.</p>
                        <a href="resume/create.php" class="btn btn-primary btn-sm">Create Resume</a>
                    </div>
                <?php else: ?>
                    <ul class="item-list">
                        <?php foreach (array_slice($resumes, 0, 5) as $resume): ?>
                            <li class="item-list-row">
                                <div>
                                    <strong><?= htmlspecialchars($resume['title'], ENT_QUOTES, 'UTF-8') ?></strong>
                                    <small class="text-muted d-block"><?= formatDate($resume['updated_at']) ?></small>
                                </div>
                                <div class="item-list-actions">
                                    <a href="resume/edit.php?id=<?= $resume['id'] ?>" class="btn btn-outline btn-xs">Edit</a>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <!-- Applications panel -->
            <div class="dashboard-panel">
                <div class="panel-header">
                    <h2>Recent Applications</h2>
                    <a href="applications/index.php" class="btn btn-outline btn-sm">View All</a>
                </div>
                <?php if (empty($applications)): ?>
                    <div class="empty-state">
                        <p>You haven't applied to any jobs yet.</p>
                        <a href="jobs/index.php" class="btn btn-primary btn-sm">Browse Jobs</a>
                    </div>
                <?php else: ?>
                    <ul class="item-list">
                        <?php foreach (array_slice($applications, 0, 5) as $app): ?>
                            <li class="item-list-row">
                                <div>
                                    <strong><?= htmlspecialchars($app['job_title'], ENT_QUOTES, 'UTF-8') ?></strong>
                                    <small class="text-muted d-block"><?= htmlspecialchars($app['company'], ENT_QUOTES, 'UTF-8') ?></small>
                                </div>
                                <?= statusBadge($app['status']) ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick job search -->
        <div class="dashboard-panel mt-4">
            <div class="panel-header">
                <h2>Find Your Next Job</h2>
                <span class="text-muted"><?= $activeJobs ?> active listings</span>
            </div>
            <form action="jobs/index.php" method="GET" class="search-bar">
                <input type="text" name="keyword" class="form-control" placeholder="Job title, company, or keyword…">
                <input type="text" name="location" class="form-control" placeholder="Location…">
                <select name="job_type" class="form-control">
                    <option value="">All Types</option>
                    <option value="full-time">Full-time</option>
                    <option value="part-time">Part-time</option>
                    <option value="contract">Contract</option>
                    <option value="remote">Remote</option>
                </select>
                <button type="submit" class="btn btn-primary">Search</button>
            </form>
        </div>
    </main>

    <footer class="footer">
        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> <?= APP_NAME ?>. All rights reserved.</p>
        </div>
    </footer>

    <script src="../assets/js/main.js"></script>
</body>
</html>
