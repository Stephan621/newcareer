<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/src/helpers/functions.php';
require_once dirname(__DIR__) . '/src/helpers/auth.php';
require_once dirname(__DIR__) . '/src/models/User.php';

requireAdmin();

$userModel = new User();

// Handle role toggle via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        flashMessage('error', 'Invalid security token.');
        redirect('users.php');
    }

    $targetId = (int) ($_POST['user_id'] ?? 0);
    $action   = $_POST['action'] ?? '';

    $currentUser = getCurrentUser();

    if ($targetId === $currentUser['id']) {
        flashMessage('error', 'You cannot modify your own account here.');
        redirect('users.php');
    }

    $target = $userModel->findById($targetId);
    if (!$target) {
        flashMessage('error', 'User not found.');
        redirect('users.php');
    }

    if ($action === 'make_admin') {
        $userModel->update($targetId, ['role' => 'admin']);
        flashMessage('success', htmlspecialchars($target['username'], ENT_QUOTES, 'UTF-8') . ' promoted to admin.');
    } elseif ($action === 'make_user') {
        $userModel->update($targetId, ['role' => 'user']);
        flashMessage('success', htmlspecialchars($target['username'], ENT_QUOTES, 'UTF-8') . ' role set to user.');
    } elseif ($action === 'delete') {
        $userModel->delete($targetId);
        flashMessage('success', 'User deleted.');
    }

    redirect('users.php');
}

$users       = $userModel->getAllUsers();
$currentUser = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users – <?= APP_NAME ?> Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container nav-container">
            <a href="../public/index.php" class="nav-logo"><?= APP_NAME ?> <span class="admin-badge">Admin</span></a>
            <button class="nav-toggle" id="navToggle" aria-label="Toggle navigation">&#9776;</button>
            <ul class="nav-links" id="navLinks">
                <li><a href="index.php">Dashboard</a></li>
                <li><a href="users.php" class="active">Users</a></li>
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
            <h1>Manage Users</h1>
            <span class="text-muted"><?= count($users) ?> registered users</span>
        </div>

        <div class="table-wrapper card">
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Registered</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr <?= $u['id'] == $currentUser['id'] ? 'class="table-highlight"' : '' ?>>
                            <td><?= $u['id'] ?></td>
                            <td><?= htmlspecialchars($u['username'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($u['email'],    ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= statusBadge($u['role']) ?></td>
                            <td><?= formatDate($u['created_at']) ?></td>
                            <td>
                                <?php if ($u['id'] != $currentUser['id']): ?>
                                    <form method="POST" action="users.php" style="display:inline">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                        <?php if ($u['role'] === 'user'): ?>
                                            <button name="action" value="make_admin" class="btn btn-outline btn-xs"
                                                onclick="return confirm('Promote to admin?')">
                                                Make Admin
                                            </button>
                                        <?php else: ?>
                                            <button name="action" value="make_user" class="btn btn-outline btn-xs"
                                                onclick="return confirm('Demote to user?')">
                                                Make User
                                            </button>
                                        <?php endif; ?>
                                        <button name="action" value="delete" class="btn btn-danger btn-xs"
                                            onclick="return confirm('Delete user <?= htmlspecialchars($u['username'], ENT_QUOTES, 'UTF-8') ?>? This cannot be undone.')">
                                            Delete
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
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
