<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Job.php';
require_once __DIR__ . '/../models/Application.php';
require_once __DIR__ . '/../models/Resume.php';

$jobModel = new Job();
$user = $_SESSION['user'] ?? null;
$errors = [];
$success = '';

// Single job view
if (isset($_GET['id'])) {
    $jobId = (int) $_GET['id'];
    $job = $jobModel->findById($jobId);
    if (!$job) {
        http_response_code(404);
        echo '<p>Job not found.</p>';
        exit;
    }

    // Handle application submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
        $resumeId   = (int) ($_POST['resume_id'] ?? 0);
        $coverLetter = trim($_POST['cover_letter'] ?? '');

        $appModel = new Application();
        if ($appModel->hasApplied($user['id'], $jobId)) {
            $errors[] = 'You have already applied for this job.';
        } elseif ($resumeId === 0) {
            $errors[] = 'Please select a resume.';
        } else {
            $appModel->create($user['id'], $jobId, $resumeId, $coverLetter);
            $success = 'Application submitted successfully!';
        }
    }

    $userResumes = $user ? (new Resume())->findByUser($user['id']) : [];
    $appModel = new Application();
    $alreadyApplied = $user && $appModel->hasApplied($user['id'], $jobId);
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($job['title']) ?> – <?= htmlspecialchars(APP_NAME) ?></title>
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
        <?php endif; ?>
    </ul>
</nav>
<main class="container">
    <a href="/jobs.php">← Back to Jobs</a>
    <h1><?= htmlspecialchars($job['title']) ?></h1>
    <p class="company"><?= htmlspecialchars($job['company']) ?></p>
    <?php if ($job['location']): ?><p>📍 <?= htmlspecialchars($job['location']) ?></p><?php endif; ?>
    <?php if ($job['salary_range']): ?><p>💰 <?= htmlspecialchars($job['salary_range']) ?></p><?php endif; ?>
    <h2>Description</h2>
    <div><?= nl2br(htmlspecialchars($job['description'])) ?></div>
    <?php if ($job['requirements']): ?>
        <h2>Requirements</h2>
        <div><?= nl2br(htmlspecialchars($job['requirements'])) ?></div>
    <?php endif; ?>

    <?php if ($success): ?><p class="success"><?= htmlspecialchars($success) ?></p><?php endif; ?>
    <?php foreach ($errors as $e): ?><p class="error"><?= htmlspecialchars($e) ?></p><?php endforeach; ?>

    <?php if ($user && !$alreadyApplied && !$success): ?>
        <h2>Apply Now</h2>
        <form method="post">
            <label>Select Resume
                <select name="resume_id" required>
                    <option value="">-- Choose a resume --</option>
                    <?php foreach ($userResumes as $r): ?>
                        <option value="<?= (int) $r['id'] ?>"><?= htmlspecialchars($r['title']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Cover Letter (optional)
                <textarea name="cover_letter" rows="5"></textarea>
            </label>
            <button type="submit" class="btn btn-primary">Submit Application</button>
        </form>
    <?php elseif (!$user): ?>
        <p><a href="/login.php">Login</a> to apply for this job.</p>
    <?php elseif ($alreadyApplied): ?>
        <p class="success">You have already applied for this job.</p>
    <?php endif; ?>
</main>
</body>
</html>
    <?php
    exit;
}

// Job listing
$jobs = $jobModel->all(activeOnly: true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Listings – <?= htmlspecialchars(APP_NAME) ?></title>
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
<main class="container">
    <h1>Job Listings</h1>
    <?php if (empty($jobs)): ?>
        <p>No jobs available at the moment.</p>
    <?php else: ?>
        <div class="job-list">
            <?php foreach ($jobs as $job): ?>
                <div class="job-card">
                    <h3><?= htmlspecialchars($job['title']) ?></h3>
                    <p class="company"><?= htmlspecialchars($job['company']) ?></p>
                    <?php if ($job['location']): ?><p>📍 <?= htmlspecialchars($job['location']) ?></p><?php endif; ?>
                    <?php if ($job['salary_range']): ?><p>💰 <?= htmlspecialchars($job['salary_range']) ?></p><?php endif; ?>
                    <a class="btn btn-outline" href="/jobs.php?id=<?= (int) $job['id'] ?>">View & Apply</a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>
</body>
</html>
