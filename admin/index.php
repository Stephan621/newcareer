<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/src/helpers/functions.php';
require_once dirname(__DIR__) . '/src/helpers/auth.php';
require_once dirname(__DIR__) . '/src/models/User.php';
require_once dirname(__DIR__) . '/src/models/Job.php';
require_once dirname(__DIR__) . '/src/models/Resume.php';
require_once dirname(__DIR__) . '/src/models/Application.php';

requireAdmin();

$userModel  = new User();
$jobModel   = new Job();
$resumeModel = new Resume();
$appModel   = new Application();

$totalUsers   = $userModel->countAll();
$totalJobs    = $jobModel->countAll();
$activeJobs   = $jobModel->countActive();
$totalResumes = $resumeModel->countAll();
$totalApps    = $appModel->countAll();
$appsByStatus = $appModel->countByStatus();

$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard – <?= APP_NAME ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container nav-container">
            <a href="../public/index.php" class="nav-logo"><?= APP_NAME ?> <span class="admin-badge">Admin</span></a>
            <button class="nav-toggle" id="navToggle" aria-label="Toggle navigation">&#9776;</button>
            <ul class="nav-links" id="navLinks">
                <li><a href="index.php" class="active">Dashboard</a></li>
                <li><a href="users.php">Users</a></li>
                <li><a href="jobs.php">Jobs</a></li>
                <li><a href="applications.php">Applications</a></li>
                <li><a href="../public/index.php">&#8617; Site</a></li>
                <li><a href="../public/logout.php" class="btn btn-outline btn-sm">Logout</a></li>
            </ul>
        </div>
    </nav>

    <main class="container section">
        <?php renderFlash(); ?>

        <div class="page-header">
            <div>
                <h1>Admin Dashboard</h1>
                <p class="text-muted">Platform overview</p>
            </div>
        </div>

        <!-- Stats -->
        <div class="stats-cards">
            <div class="stat-card">
                <div class="stat-card-icon icon-blue">👥</div>
                <div>
                    <div class="stat-card-value"><?= $totalUsers ?></div>
                    <div class="stat-card-label">Total Users</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon icon-green">💼</div>
                <div>
                    <div class="stat-card-value"><?= $totalJobs ?></div>
                    <div class="stat-card-label">Total Jobs</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon icon-teal">✅</div>
                <div>
                    <div class="stat-card-value"><?= $activeJobs ?></div>
                    <div class="stat-card-label">Active Jobs</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon icon-purple">📄</div>
                <div>
                    <div class="stat-card-value"><?= $totalResumes ?></div>
                    <div class="stat-card-label">Resumes</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon icon-yellow">📨</div>
                <div>
                    <div class="stat-card-value"><?= $totalApps ?></div>
                    <div class="stat-card-label">Applications</div>
                </div>
            </div>
        </div>

        <!-- Applications breakdown -->
        <div class="dashboard-grid">
            <div class="dashboard-panel">
                <div class="panel-header"><h2>Applications by Status</h2></div>
                <div class="status-breakdown">
                    <?php foreach (Application::STATUSES as $status): ?>
                        <div class="status-row">
                            <span><?= statusBadge($status) ?></span>
                            <strong><?= $appsByStatus[$status] ?? 0 ?></strong>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="dashboard-panel">
                <div class="panel-header"><h2>Quick Actions</h2></div>
                <div class="quick-actions">
                    <a href="jobs/create.php" class="btn btn-primary">+ Post New Job</a>
                    <a href="users.php"        class="btn btn-outline">Manage Users</a>
                    <a href="jobs.php"          class="btn btn-outline">Manage Jobs</a>
                    <a href="applications.php"  class="btn btn-outline">Review Applications</a>
                </div>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> <?= APP_NAME ?> Admin Panel.</p>
        </div>
    </footer>

    <script src="../assets/js/main.js"></script>
</body>
</html>
