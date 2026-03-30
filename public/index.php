<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/src/helpers/functions.php';
require_once dirname(__DIR__) . '/src/helpers/auth.php';
require_once dirname(__DIR__) . '/src/models/Job.php';

$jobModel  = new Job();
$featured  = $jobModel->getFeatured(6);
$totalJobs = $jobModel->countActive();
$user      = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?> – Find Your Dream Career</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container nav-container">
            <a href="index.php" class="nav-logo"><?= APP_NAME ?></a>
            <button class="nav-toggle" id="navToggle" aria-label="Toggle navigation">&#9776;</button>
            <ul class="nav-links" id="navLinks">
                <li><a href="index.php" class="active">Home</a></li>
                <li><a href="jobs/index.php">Jobs</a></li>
                <?php if ($user): ?>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <?php if ($user['role'] === 'admin'): ?>
                        <li><a href="../admin/index.php">Admin</a></li>
                    <?php endif; ?>
                    <li><a href="logout.php" class="btn btn-outline btn-sm">Logout</a></li>
                <?php else: ?>
                    <li><a href="login.php">Login</a></li>
                    <li><a href="register.php" class="btn btn-primary btn-sm">Get Started</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <!-- Hero -->
    <section class="hero">
        <div class="container hero-content">
            <h1>Launch Your Next Career Move</h1>
            <p>Browse <?= $totalJobs ?> active job openings, build your resume, and apply in minutes.</p>
            <div class="hero-actions">
                <a href="jobs/index.php" class="btn btn-primary btn-lg">Browse Jobs</a>
                <?php if (!$user): ?>
                    <a href="register.php" class="btn btn-outline btn-lg">Create Account</a>
                <?php else: ?>
                    <a href="resume/create.php" class="btn btn-outline btn-lg">Build Resume</a>
                <?php endif; ?>
            </div>
            <!-- Quick search -->
            <form action="jobs/index.php" method="GET" class="hero-search">
                <input type="text" name="keyword" placeholder="Job title or keyword…" class="form-control">
                <input type="text" name="location" placeholder="Location…" class="form-control">
                <button type="submit" class="btn btn-primary">Search</button>
            </form>
        </div>
    </section>

    <!-- Stats bar -->
    <section class="stats-bar">
        <div class="container stats-grid">
            <div class="stat-item">
                <span class="stat-number"><?= $totalJobs ?>+</span>
                <span class="stat-label">Active Jobs</span>
            </div>
            <div class="stat-item">
                <span class="stat-number">500+</span>
                <span class="stat-label">Companies</span>
            </div>
            <div class="stat-item">
                <span class="stat-number">10K+</span>
                <span class="stat-label">Candidates</span>
            </div>
            <div class="stat-item">
                <span class="stat-number">95%</span>
                <span class="stat-label">Satisfaction</span>
            </div>
        </div>
    </section>

    <!-- Featured jobs -->
    <section class="section">
        <div class="container">
            <?php renderFlash(); ?>
            <div class="section-header">
                <h2>Featured Jobs</h2>
                <a href="jobs/index.php" class="btn btn-outline">View All</a>
            </div>

            <?php if (empty($featured)): ?>
                <p class="text-center text-muted">No jobs available at this time. Check back soon!</p>
            <?php else: ?>
                <div class="job-grid">
                    <?php foreach ($featured as $job): ?>
                        <div class="job-card">
                            <div class="job-card-header">
                                <h3 class="job-title">
                                    <a href="jobs/view.php?id=<?= $job['id'] ?>"><?= htmlspecialchars($job['title'], ENT_QUOTES, 'UTF-8') ?></a>
                                </h3>
                                <?= statusBadge($job['job_type']) ?>
                            </div>
                            <p class="job-company">
                                <span class="icon">🏢</span> <?= htmlspecialchars($job['company'], ENT_QUOTES, 'UTF-8') ?>
                            </p>
                            <p class="job-location">
                                <span class="icon">📍</span> <?= htmlspecialchars($job['location'], ENT_QUOTES, 'UTF-8') ?>
                            </p>
                            <?php if ($job['salary_range']): ?>
                                <p class="job-salary">
                                    <span class="icon">💰</span> <?= htmlspecialchars($job['salary_range'], ENT_QUOTES, 'UTF-8') ?>
                                </p>
                            <?php endif; ?>
                            <p class="job-excerpt"><?= truncate($job['description'], 120) ?></p>
                            <div class="job-card-footer">
                                <span class="job-date"><?= timeAgo($job['created_at']) ?></span>
                                <a href="jobs/view.php?id=<?= $job['id'] ?>" class="btn btn-primary btn-sm">View &amp; Apply</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- How it works -->
    <section class="section section-alt">
        <div class="container">
            <h2 class="text-center">How It Works</h2>
            <div class="steps-grid">
                <div class="step-card">
                    <div class="step-icon">📝</div>
                    <h3>Create Your Profile</h3>
                    <p>Sign up and build a professional resume in minutes with our guided editor.</p>
                </div>
                <div class="step-card">
                    <div class="step-icon">🔍</div>
                    <h3>Find the Right Job</h3>
                    <p>Browse and search hundreds of curated opportunities that match your skills.</p>
                </div>
                <div class="step-card">
                    <div class="step-icon">🚀</div>
                    <h3>Apply with One Click</h3>
                    <p>Send your resume and a personalised cover letter directly to employers.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <?php if (!$user): ?>
    <section class="section cta-section">
        <div class="container text-center">
            <h2>Ready to take the next step?</h2>
            <p>Join thousands of professionals who have found their dream jobs through <?= APP_NAME ?>.</p>
            <a href="register.php" class="btn btn-primary btn-lg">Create Free Account</a>
        </div>
    </section>
    <?php endif; ?>

    <!-- Footer -->
    <footer class="footer">
        <div class="container footer-content">
            <div class="footer-brand">
                <span class="nav-logo"><?= APP_NAME ?></span>
                <p>Your partner in career growth.</p>
            </div>
            <div class="footer-links">
                <h4>Quick Links</h4>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="jobs/index.php">Browse Jobs</a></li>
                    <li><a href="register.php">Register</a></li>
                    <li><a href="login.php">Login</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> <?= APP_NAME ?>. All rights reserved.</p>
        </div>
    </footer>

    <script src="../assets/js/main.js"></script>
</body>
</html>
