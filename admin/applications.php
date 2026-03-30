<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../models/Application.php';

$appModel = new Application();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['app_id'], $_POST['status'])) {
    $allowedStatuses = ['pending', 'reviewed', 'accepted', 'rejected'];
    $status = $_POST['status'];
    if (in_array($status, $allowedStatuses, true)) {
        $appModel->updateStatus((int) $_POST['app_id'], $status);
        $message = 'Status updated.';
    }
}

$applications = $appModel->all();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Applications – <?= htmlspecialchars(APP_NAME) ?></title>
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
    <h1>Applications</h1>
    <?php if ($message): ?><p class="success"><?= htmlspecialchars($message) ?></p><?php endif; ?>
    <table class="data-table">
        <thead>
            <tr>
                <th>Applicant</th><th>Job</th><th>Resume</th><th>Applied</th><th>Status</th><th>Update</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($applications as $app): ?>
            <tr>
                <td>
                    <?= htmlspecialchars($app['user_name']) ?><br>
                    <small><?= htmlspecialchars($app['user_email']) ?></small>
                </td>
                <td><?= htmlspecialchars($app['job_title']) ?> @ <?= htmlspecialchars($app['company']) ?></td>
                <td><?= htmlspecialchars($app['resume_title']) ?></td>
                <td><?= htmlspecialchars($app['applied_at']) ?></td>
                <td><span class="badge <?= htmlspecialchars($app['status']) ?>"><?= htmlspecialchars($app['status']) ?></span></td>
                <td>
                    <form method="post" style="display:flex; gap:4px;">
                        <input type="hidden" name="app_id" value="<?= (int) $app['id'] ?>">
                        <select name="status">
                            <?php foreach (['pending', 'reviewed', 'accepted', 'rejected'] as $s): ?>
                                <option value="<?= $s ?>" <?= $s === $app['status'] ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-primary">Save</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</main>
</body>
</html>
