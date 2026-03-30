<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/src/helpers/functions.php';
require_once dirname(__DIR__) . '/src/helpers/auth.php';
require_once dirname(__DIR__) . '/src/models/Application.php';

requireAdmin();

$appModel = new Application();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        flashMessage('error', 'Invalid security token.');
        redirect('applications.php');
    }

    $appId     = (int) ($_POST['app_id'] ?? 0);
    $newStatus = sanitize($_POST['status'] ?? '');

    if ($appId > 0 && in_array($newStatus, Application::STATUSES, true)) {
        $appModel->update($appId, $newStatus);
        flashMessage('success', 'Application status updated to ' . $newStatus . '.');
    }

    redirect('applications.php');
}

$applications = $appModel->getAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Applications – <?= APP_NAME ?> Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container nav-container">
            <a href="../public/index.php" class="nav-logo"><?= APP_NAME ?> <span class="admin-badge">Admin</span></a>
            <button class="nav-toggle" id="navToggle" aria-label="Toggle navigation">&#9776;</button>
            <ul class="nav-links" id="navLinks">
                <li><a href="index.php">Dashboard</a></li>
                <li><a href="users.php">Users</a></li>
                <li><a href="jobs.php">Jobs</a></li>
                <li><a href="applications.php" class="active">Applications</a></li>
                <li><a href="../public/index.php">&#8617; Site</a></li>
                <li><a href="../public/logout.php" class="btn btn-outline btn-sm">Logout</a></li>
            </ul>
        </div>
    </nav>

    <main class="container section">
        <?php renderFlash(); ?>

        <div class="page-header">
            <h1>All Applications</h1>
            <span class="text-muted"><?= count($applications) ?> total</span>
        </div>

        <?php if (empty($applications)): ?>
            <div class="empty-state card">
                <p>No applications submitted yet.</p>
            </div>
        <?php else: ?>
            <div class="table-wrapper card">
                <table class="table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Applicant</th>
                            <th>Job</th>
                            <th>Company</th>
                            <th>Resume</th>
                            <th>Status</th>
                            <th>Applied</th>
                            <th>Update Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($applications as $app): ?>
                            <tr>
                                <td><?= $app['id'] ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($app['username'], ENT_QUOTES, 'UTF-8') ?></strong><br>
                                    <small class="text-muted"><?= htmlspecialchars($app['email'], ENT_QUOTES, 'UTF-8') ?></small>
                                </td>
                                <td>
                                    <a href="../public/jobs/view.php?id=<?= $app['job_id'] ?>">
                                        <?= htmlspecialchars($app['job_title'], ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                </td>
                                <td><?= htmlspecialchars($app['company'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= $app['resume_title']
                                        ? htmlspecialchars($app['resume_title'], ENT_QUOTES, 'UTF-8')
                                        : '<span class="text-muted">None</span>' ?></td>
                                <td><?= statusBadge($app['status']) ?></td>
                                <td><?= formatDate($app['created_at']) ?></td>
                                <td>
                                    <form method="POST" action="applications.php" style="display:flex;gap:.5rem;align-items:center;">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="app_id" value="<?= $app['id'] ?>">
                                        <select name="status" class="form-control form-control-xs">
                                            <?php foreach (Application::STATUSES as $s): ?>
                                                <option value="<?= $s ?>" <?= $app['status'] === $s ? 'selected' : '' ?>>
                                                    <?= ucfirst($s) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" class="btn btn-outline btn-xs">Save</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </main>

    <footer class="footer">
        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> <?= APP_NAME ?> Admin Panel.</p>
        </div>
    </footer>

    <script src="../assets/js/main.js"></script>
</body>
</html>
