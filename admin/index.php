<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Job.php';
require_once __DIR__ . '/../models/Application.php';

$db = Database::getInstance();

$totalUsers = (int) $db->query('SELECT COUNT(*) FROM users WHERE role = "user"')->fetchColumn();
$totalJobs  = (int) $db->query('SELECT COUNT(*) FROM jobs')->fetchColumn();
$totalApps  = (int) $db->query('SELECT COUNT(*) FROM applications')->fetchColumn();
$pendingApps = (int) $db->query('SELECT COUNT(*) FROM applications WHERE status = "pending"')->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard – <?= htmlspecialchars(APP_NAME) ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<nav class="navbar">
    <a class="brand" href="/admin/index.php">Admin Panel</a>
    <ul>
        <li><a href="/admin/users.php">Users</a></li>
        <li><a href="/admin/jobs.php">Jobs</a></li>
        <li><a href="/admin/applications.php">Applications</a></li>
        <li><a href="/logout.php">Logout</a></li>
    </ul>
</nav>
<main class="container">
    <h1>Dashboard</h1>
    <div class="stats-grid">
        <div class="stat-card">
            <h3><?= $totalUsers ?></h3>
            <p>Registered Users</p>
        </div>
        <div class="stat-card">
            <h3><?= $totalJobs ?></h3>
            <p>Job Postings</p>
        </div>
        <div class="stat-card">
            <h3><?= $totalApps ?></h3>
            <p>Total Applications</p>
        </div>
        <div class="stat-card">
            <h3><?= $pendingApps ?></h3>
            <p>Pending Reviews</p>
        </div>
    </div>
    <div class="quick-links">
        <a class="btn btn-primary" href="/admin/jobs.php?action=new">Post a New Job</a>
        <a class="btn btn-outline" href="/admin/applications.php">Review Applications</a>
    </div>
</main>
</body>
</html>
