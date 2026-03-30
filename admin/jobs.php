<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../models/Job.php';

$jobModel = new Job();
$errors = [];
$message = '';
$editJob = null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $data = [
            'title'        => trim($_POST['title'] ?? ''),
            'company'      => trim($_POST['company'] ?? ''),
            'location'     => trim($_POST['location'] ?? ''),
            'description'  => trim($_POST['description'] ?? ''),
            'requirements' => trim($_POST['requirements'] ?? ''),
            'salary_range' => trim($_POST['salary_range'] ?? ''),
            'is_active'    => isset($_POST['is_active']) ? 1 : 0,
        ];

        if ($data['title'] === '' || $data['company'] === '' || $data['description'] === '') {
            $errors[] = 'Title, company, and description are required.';
        } elseif ($action === 'create') {
            $jobModel->create($data);
            $message = 'Job posted successfully.';
        } else {
            $jobId = (int) ($_POST['job_id'] ?? 0);
            $jobModel->update($jobId, $data);
            $message = 'Job updated successfully.';
        }
    } elseif ($action === 'delete') {
        $jobId = (int) ($_POST['job_id'] ?? 0);
        $jobModel->delete($jobId);
        $message = 'Job deleted.';
    }
}

if (isset($_GET['edit'])) {
    $editJob = $jobModel->findById((int) $_GET['edit']);
}

$showForm = (isset($_GET['action']) && $_GET['action'] === 'new') || $editJob;
$jobs = $jobModel->all(activeOnly: false);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Jobs – <?= htmlspecialchars(APP_NAME) ?></title>
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
    <h1>Manage Jobs</h1>
    <?php if ($message): ?><p class="success"><?= htmlspecialchars($message) ?></p><?php endif; ?>
    <?php foreach ($errors as $e): ?><p class="error"><?= htmlspecialchars($e) ?></p><?php endforeach; ?>

    <a class="btn btn-primary" href="/admin/jobs.php?action=new">+ Post New Job</a>

    <?php if ($showForm): ?>
    <section class="card">
        <h2><?= $editJob ? 'Edit Job' : 'New Job Posting' ?></h2>
        <form method="post">
            <input type="hidden" name="action" value="<?= $editJob ? 'update' : 'create' ?>">
            <?php if ($editJob): ?>
                <input type="hidden" name="job_id" value="<?= (int) $editJob['id'] ?>">
            <?php endif; ?>
            <label>Job Title * <input type="text" name="title" required value="<?= htmlspecialchars($editJob['title'] ?? '') ?>"></label>
            <label>Company * <input type="text" name="company" required value="<?= htmlspecialchars($editJob['company'] ?? '') ?>"></label>
            <label>Location <input type="text" name="location" value="<?= htmlspecialchars($editJob['location'] ?? '') ?>"></label>
            <label>Salary Range <input type="text" name="salary_range" value="<?= htmlspecialchars($editJob['salary_range'] ?? '') ?>"></label>
            <label>Description * <textarea name="description" rows="5" required><?= htmlspecialchars($editJob['description'] ?? '') ?></textarea></label>
            <label>Requirements <textarea name="requirements" rows="4"><?= htmlspecialchars($editJob['requirements'] ?? '') ?></textarea></label>
            <label><input type="checkbox" name="is_active" <?= (!$editJob || $editJob['is_active']) ? 'checked' : '' ?>> Active</label>
            <button type="submit" class="btn btn-primary"><?= $editJob ? 'Update Job' : 'Post Job' ?></button>
            <a class="btn btn-outline" href="/admin/jobs.php">Cancel</a>
        </form>
    </section>
    <?php endif; ?>

    <table class="data-table">
        <thead>
            <tr><th>Title</th><th>Company</th><th>Location</th><th>Status</th><th>Actions</th></tr>
        </thead>
        <tbody>
            <?php foreach ($jobs as $job): ?>
            <tr>
                <td><?= htmlspecialchars($job['title']) ?></td>
                <td><?= htmlspecialchars($job['company']) ?></td>
                <td><?= htmlspecialchars($job['location'] ?? '–') ?></td>
                <td><?= $job['is_active'] ? '<span class="badge active">Active</span>' : '<span class="badge inactive">Inactive</span>' ?></td>
                <td>
                    <a class="btn btn-outline" href="/admin/jobs.php?edit=<?= (int) $job['id'] ?>">Edit</a>
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="job_id" value="<?= (int) $job['id'] ?>">
                        <button class="btn btn-danger" onclick="return confirm('Delete this job?')">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</main>
</body>
</html>
