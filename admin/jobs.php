<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/src/helpers/functions.php';
require_once dirname(__DIR__) . '/src/helpers/auth.php';
require_once dirname(__DIR__) . '/src/models/Job.php';

requireAdmin();

$jobModel = new Job();

// Handle delete via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        flashMessage('error', 'Invalid security token.');
        redirect('jobs.php');
    }

    $action = $_POST['action'] ?? '';
    $id     = (int) ($_POST['job_id'] ?? 0);

    if ($id > 0) {
        if ($action === 'delete') {
            $jobModel->delete($id);
            flashMessage('success', 'Job listing deleted.');
        } elseif ($action === 'toggle_status') {
            $job = $jobModel->findById($id);
            if ($job) {
                $newStatus = $job['status'] === 'active' ? 'inactive' : 'active';
                $jobModel->update($id, ['status' => $newStatus]);
                flashMessage('success', 'Job status updated to ' . $newStatus . '.');
            }
        }
    }
    redirect('jobs.php');
}

$jobs = $jobModel->getAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Jobs – <?= APP_NAME ?> Admin</title>
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
                <li><a href="jobs.php" class="active">Jobs</a></li>
                <li><a href="applications.php">Applications</a></li>
                <li><a href="../public/index.php">&#8617; Site</a></li>
                <li><a href="../public/logout.php" class="btn btn-outline btn-sm">Logout</a></li>
            </ul>
        </div>
    </nav>

    <main class="container section">
        <?php renderFlash(); ?>

        <div class="page-header">
            <h1>Manage Jobs</h1>
            <a href="jobs/create.php" class="btn btn-primary">+ Post New Job</a>
        </div>

        <?php if (empty($jobs)): ?>
            <div class="empty-state card">
                <p>No job listings yet.</p>
                <a href="jobs/create.php" class="btn btn-primary">Create First Job</a>
            </div>
        <?php else: ?>
            <div class="table-wrapper card">
                <table class="table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Title</th>
                            <th>Company</th>
                            <th>Location</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Posted</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($jobs as $job): ?>
                            <tr>
                                <td><?= $job['id'] ?></td>
                                <td>
                                    <a href="../public/jobs/view.php?id=<?= $job['id'] ?>">
                                        <?= htmlspecialchars($job['title'],   ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                </td>
                                <td><?= htmlspecialchars($job['company'],  ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($job['location'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= statusBadge($job['job_type']) ?></td>
                                <td><?= statusBadge($job['status'])   ?></td>
                                <td><?= formatDate($job['created_at']) ?></td>
                                <td>
                                    <a href="jobs/edit.php?id=<?= $job['id'] ?>" class="btn btn-outline btn-xs">Edit</a>
                                    <form method="POST" action="jobs.php" style="display:inline">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="job_id" value="<?= $job['id'] ?>">
                                        <button name="action" value="toggle_status" class="btn btn-outline btn-xs">
                                            <?= $job['status'] === 'active' ? 'Deactivate' : 'Activate' ?>
                                        </button>
                                        <button name="action" value="delete" class="btn btn-danger btn-xs"
                                            onclick="return confirm('Delete this job listing?')">
                                            Delete
                                        </button>
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
