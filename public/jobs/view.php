<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/src/helpers/functions.php';
require_once dirname(__DIR__, 2) . '/src/helpers/auth.php';
require_once dirname(__DIR__, 2) . '/src/models/Job.php';
require_once dirname(__DIR__, 2) . '/src/models/Application.php';

$user     = getCurrentUser();
$jobModel = new Job();

$id  = (int) ($_GET['id'] ?? 0);
$job = $jobModel->findById($id);

if (!$job) {
    flashMessage('error', 'Job listing not found.');
    redirect('index.php');
}

$hasApplied = false;
if ($user) {
    $appModel   = new Application();
    $hasApplied = $appModel->hasApplied($user['id'], $id);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($job['title'], ENT_QUOTES, 'UTF-8') ?> – <?= APP_NAME ?></title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container nav-container">
            <a href="../index.php" class="nav-logo"><?= APP_NAME ?></a>
            <button class="nav-toggle" id="navToggle" aria-label="Toggle navigation">&#9776;</button>
            <ul class="nav-links" id="navLinks">
                <li><a href="../index.php">Home</a></li>
                <li><a href="index.php" class="active">Jobs</a></li>
                <?php if ($user): ?>
                    <li><a href="../dashboard.php">Dashboard</a></li>
                    <?php if ($user['role'] === 'admin'): ?>
                        <li><a href="../../admin/index.php">Admin</a></li>
                    <?php endif; ?>
                    <li><a href="../logout.php" class="btn btn-outline btn-sm">Logout</a></li>
                <?php else: ?>
                    <li><a href="../login.php">Login</a></li>
                    <li><a href="../register.php" class="btn btn-primary btn-sm">Register</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <main class="container section">
        <?php renderFlash(); ?>

        <a href="index.php" class="back-link">&larr; Back to Jobs</a>

        <div class="job-detail-grid">
            <!-- Main content -->
            <div class="job-detail-main">
                <div class="card">
                    <div class="card-body">
                        <div class="job-detail-header">
                            <div>
                                <h1><?= htmlspecialchars($job['title'], ENT_QUOTES, 'UTF-8') ?></h1>
                                <p class="job-company-large">
                                    <?= htmlspecialchars($job['company'], ENT_QUOTES, 'UTF-8') ?>
                                </p>
                            </div>
                            <?= statusBadge($job['job_type']) ?>
                        </div>

                        <div class="job-meta job-meta-detail">
                            <span>📍 <?= htmlspecialchars($job['location'], ENT_QUOTES, 'UTF-8') ?></span>
                            <?php if ($job['salary_range']): ?>
                                <span>💰 <?= htmlspecialchars($job['salary_range'], ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                            <span>🕒 Posted <?= timeAgo($job['created_at']) ?></span>
                        </div>

                        <hr>

                        <h2>Job Description</h2>
                        <div class="prose"><?= nl2brSafe($job['description']) ?></div>

                        <?php if ($job['requirements']): ?>
                            <h2>Requirements</h2>
                            <div class="prose"><?= nl2brSafe($job['requirements']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <aside class="job-detail-sidebar">
                <div class="card sidebar-card">
                    <div class="card-body">
                        <h3>Job Details</h3>
                        <dl class="job-details-list">
                            <dt>Company</dt>
                            <dd><?= htmlspecialchars($job['company'],  ENT_QUOTES, 'UTF-8') ?></dd>
                            <dt>Location</dt>
                            <dd><?= htmlspecialchars($job['location'], ENT_QUOTES, 'UTF-8') ?></dd>
                            <dt>Job Type</dt>
                            <dd><?= statusBadge($job['job_type']) ?></dd>
                            <?php if ($job['salary_range']): ?>
                                <dt>Salary</dt>
                                <dd><?= htmlspecialchars($job['salary_range'], ENT_QUOTES, 'UTF-8') ?></dd>
                            <?php endif; ?>
                            <dt>Posted</dt>
                            <dd><?= formatDate($job['created_at']) ?></dd>
                        </dl>

                        <?php if ($job['status'] !== 'active'): ?>
                            <div class="alert alert-warning mt-2">This job is no longer accepting applications.</div>
                        <?php elseif (!$user): ?>
                            <a href="../login.php" class="btn btn-primary btn-block mt-2">Login to Apply</a>
                            <a href="../register.php" class="btn btn-outline btn-block mt-1">Create Account</a>
                        <?php elseif ($hasApplied): ?>
                            <div class="alert alert-success mt-2">✅ You have already applied for this job.</div>
                            <a href="../applications/index.php" class="btn btn-outline btn-block mt-1">View Applications</a>
                        <?php else: ?>
                            <a href="apply.php?id=<?= $job['id'] ?>" class="btn btn-primary btn-block mt-2">Apply Now</a>
                        <?php endif; ?>
                    </div>
                </div>
            </aside>
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
