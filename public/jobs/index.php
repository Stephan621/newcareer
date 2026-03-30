<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/src/helpers/functions.php';
require_once dirname(__DIR__, 2) . '/src/helpers/auth.php';
require_once dirname(__DIR__, 2) . '/src/models/Job.php';

$jobModel = new Job();
$user     = getCurrentUser();

// Search / filter parameters
$keyword  = sanitize($_GET['keyword']  ?? '');
$location = sanitize($_GET['location'] ?? '');
$jobType  = sanitize($_GET['job_type'] ?? '');

$jobs = $jobModel->search($keyword, $location, $jobType);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Jobs – <?= APP_NAME ?></title>
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
                    <li><a href="../resume/index.php">Resumes</a></li>
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

        <div class="page-header">
            <h1>Browse Jobs</h1>
            <span class="text-muted"><?= count($jobs) ?> result<?= count($jobs) !== 1 ? 's' : '' ?></span>
        </div>

        <!-- Search / filter bar -->
        <div class="card search-card">
            <form method="GET" action="index.php" class="search-bar" id="jobSearchForm">
                <input
                    type="text"
                    name="keyword"
                    class="form-control"
                    placeholder="Job title, company, keyword…"
                    value="<?= htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8') ?>"
                >
                <input
                    type="text"
                    name="location"
                    class="form-control"
                    placeholder="Location…"
                    value="<?= htmlspecialchars($location, ENT_QUOTES, 'UTF-8') ?>"
                >
                <select name="job_type" class="form-control">
                    <option value="">All Types</option>
                    <?php foreach (Job::TYPES as $type): ?>
                        <option value="<?= $type ?>" <?= $jobType === $type ? 'selected' : '' ?>>
                            <?= ucfirst($type) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-primary">Search</button>
                <?php if ($keyword || $location || $jobType): ?>
                    <a href="index.php" class="btn btn-outline">Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <?php if (empty($jobs)): ?>
            <div class="empty-state">
                <div class="empty-icon">🔍</div>
                <h3>No jobs found</h3>
                <p>Try adjusting your search filters.</p>
                <a href="index.php" class="btn btn-primary">View All Jobs</a>
            </div>
        <?php else: ?>
            <div class="job-list">
                <?php foreach ($jobs as $job): ?>
                    <div class="job-list-item card">
                        <div class="job-list-main">
                            <div class="job-list-header">
                                <h3>
                                    <a href="view.php?id=<?= $job['id'] ?>"><?= htmlspecialchars($job['title'], ENT_QUOTES, 'UTF-8') ?></a>
                                </h3>
                                <?= statusBadge($job['job_type']) ?>
                            </div>
                            <div class="job-meta">
                                <span>🏢 <?= htmlspecialchars($job['company'],  ENT_QUOTES, 'UTF-8') ?></span>
                                <span>📍 <?= htmlspecialchars($job['location'], ENT_QUOTES, 'UTF-8') ?></span>
                                <?php if ($job['salary_range']): ?>
                                    <span>💰 <?= htmlspecialchars($job['salary_range'], ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endif; ?>
                                <span>🕒 <?= timeAgo($job['created_at']) ?></span>
                            </div>
                            <p class="job-excerpt"><?= truncate($job['description'], 200) ?></p>
                        </div>
                        <div class="job-list-action">
                            <a href="view.php?id=<?= $job['id'] ?>" class="btn btn-primary">View Job</a>
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
