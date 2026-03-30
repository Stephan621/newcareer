<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../models/User.php';

$userModel = new User();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $deleteId = (int) $_POST['delete_id'];
    if ($deleteId !== (int) $_SESSION['user']['id']) {
        $userModel->delete($deleteId);
        $message = 'User deleted.';
    } else {
        $message = 'You cannot delete your own account.';
    }
}

$users = $userModel->all();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users – <?= htmlspecialchars(APP_NAME) ?></title>
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
    <h1>Manage Users</h1>
    <?php if ($message): ?><p class="success"><?= htmlspecialchars($message) ?></p><?php endif; ?>
    <table class="data-table">
        <thead>
            <tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Joined</th><th>Actions</th></tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
            <tr>
                <td><?= (int) $u['id'] ?></td>
                <td><?= htmlspecialchars($u['name']) ?></td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td><?= htmlspecialchars($u['role']) ?></td>
                <td><?= htmlspecialchars($u['created_at']) ?></td>
                <td>
                    <?php if ((int) $u['id'] !== (int) $_SESSION['user']['id']): ?>
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="delete_id" value="<?= (int) $u['id'] ?>">
                        <button class="btn btn-danger" onclick="return confirm('Delete this user?')">Delete</button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</main>
</body>
</html>
