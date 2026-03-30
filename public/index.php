<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Job.php';

$jobModel = new Job();
$jobs = $jobModel->all(activeOnly: true);
$user = $_SESSION['user'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(APP_NAME) ?> – Find Your Next Career</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<nav class="navbar">
    <a class="brand" href="/"><?= htmlspecialchars(APP_NAME) ?></a>
    <ul>
        <li><a href="/jobs.php">Jobs</a></li>
        <?php if ($user): ?>
            <li><a href="/resume-builder.php">My Resumes</a></li>
            <li><a href="/logout.php">Logout</a></li>
        <?php else: ?>
            <li><a href="/login.php">Login</a></li>
            <li><a href="/register.php">Register</a></li>
        <?php endif; ?>
    </ul>
</nav>

<header class="hero">
    <h1>Launch Your Next Career</h1>
    <p>Build a stunning resume and discover great job opportunities.</p>
    <a class="btn btn-primary" href="/register.php">Get Started</a>
</header>

<main class="container">
    <h2>Latest Job Openings</h2>
    <?php if (empty($jobs)): ?>
        <p>No job postings available yet. Check back soon!</p>
    <?php else: ?>
        <div class="job-list">
            <?php foreach ($jobs as $job): ?>
                <div class="job-card">
                    <h3><?= htmlspecialchars($job['title']) ?></h3>
                    <p class="company"><?= htmlspecialchars($job['company']) ?></p>
                    <?php if ($job['location']): ?>
                        <p class="location">📍 <?= htmlspecialchars($job['location']) ?></p>
                    <?php endif; ?>
                    <?php if ($job['salary_range']): ?>
                        <p class="salary">💰 <?= htmlspecialchars($job['salary_range']) ?></p>
                    <?php endif; ?>
                    <a class="btn btn-outline" href="/jobs.php?id=<?= (int) $job['id'] ?>">View Details</a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<footer>
    <p>&copy; <?= date('Y') ?> <?= htmlspecialchars(APP_NAME) ?>. All rights reserved.</p>
</footer>
</body>
</html>
