<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Resume.php';

if (!isset($_SESSION['user'])) {
    header('Location: /login.php');
    exit;
}

$userId = (int) $_SESSION['user']['id'];
$resumeModel = new Resume();
$errors = [];
$success = '';

// Handle resume creation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create_resume') {
        $title   = trim($_POST['title'] ?? '');
        $summary = trim($_POST['summary'] ?? '');
        if ($title === '') {
            $errors[] = 'Resume title is required.';
        } else {
            $resumeModel->create($userId, $title, $summary);
            $success = 'Resume created successfully.';
        }
    } elseif ($action === 'add_education') {
        $resumeId = (int) ($_POST['resume_id'] ?? 0);
        $resumeModel->addEducation($resumeId, [
            'institution'    => trim($_POST['institution'] ?? ''),
            'degree'         => trim($_POST['degree'] ?? ''),
            'field_of_study' => trim($_POST['field_of_study'] ?? ''),
            'start_year'     => $_POST['start_year'] ?? null,
            'end_year'       => $_POST['end_year'] ?? null,
            'description'    => trim($_POST['edu_description'] ?? ''),
        ]);
        $success = 'Education added.';
    } elseif ($action === 'add_experience') {
        $resumeId = (int) ($_POST['resume_id'] ?? 0);
        $isCurrent = isset($_POST['is_current']) ? 1 : 0;
        $resumeModel->addExperience($resumeId, [
            'company'     => trim($_POST['company'] ?? ''),
            'position'    => trim($_POST['position'] ?? ''),
            'start_date'  => $_POST['start_date'] ?? null,
            'end_date'    => $isCurrent ? null : ($_POST['end_date'] ?? null),
            'is_current'  => $isCurrent,
            'description' => trim($_POST['exp_description'] ?? ''),
        ]);
        $success = 'Experience added.';
    } elseif ($action === 'add_skill') {
        $resumeId = (int) ($_POST['resume_id'] ?? 0);
        $skillName = trim($_POST['skill_name'] ?? '');
        $skillLevel = $_POST['skill_level'] ?? 'intermediate';
        if ($skillName !== '') {
            $resumeModel->addSkill($resumeId, $skillName, $skillLevel);
            $success = 'Skill added.';
        }
    } elseif ($action === 'delete_resume') {
        $resumeId = (int) ($_POST['resume_id'] ?? 0);
        $resume = $resumeModel->findById($resumeId);
        if ($resume && (int) $resume['user_id'] === $userId) {
            $resumeModel->delete($resumeId);
            $success = 'Resume deleted.';
        }
    }
}

$resumes = $resumeModel->findByUser($userId);
$selectedId = isset($_GET['resume']) ? (int) $_GET['resume'] : ($resumes[0]['id'] ?? 0);
$selectedResume = $selectedId ? $resumeModel->findById($selectedId) : null;
$education  = $selectedId ? $resumeModel->getEducation($selectedId) : [];
$experience = $selectedId ? $resumeModel->getExperience($selectedId) : [];
$skills     = $selectedId ? $resumeModel->getSkills($selectedId) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resume Builder – <?= htmlspecialchars(APP_NAME) ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<nav class="navbar">
    <a class="brand" href="/"><?= htmlspecialchars(APP_NAME) ?></a>
    <ul>
        <li><a href="/jobs.php">Jobs</a></li>
        <li><a href="/resume-builder.php">My Resumes</a></li>
        <li><a href="/logout.php">Logout</a></li>
    </ul>
</nav>
<main class="container">
    <h1>Resume Builder</h1>
    <?php if ($success): ?><p class="success"><?= htmlspecialchars($success) ?></p><?php endif; ?>
    <?php foreach ($errors as $e): ?><p class="error"><?= htmlspecialchars($e) ?></p><?php endforeach; ?>

    <section class="card">
        <h2>Create New Resume</h2>
        <form method="post">
            <input type="hidden" name="action" value="create_resume">
            <label>Title <input type="text" name="title" required></label>
            <label>Professional Summary <textarea name="summary" rows="4"></textarea></label>
            <button type="submit" class="btn btn-primary">Create Resume</button>
        </form>
    </section>

    <?php if (!empty($resumes)): ?>
    <section class="card">
        <h2>My Resumes</h2>
        <div class="resume-tabs">
            <?php foreach ($resumes as $r): ?>
                <a class="tab <?= $r['id'] === $selectedId ? 'active' : '' ?>"
                   href="/resume-builder.php?resume=<?= (int) $r['id'] ?>">
                    <?= htmlspecialchars($r['title']) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    <?php if ($selectedResume): ?>
    <div class="resume-detail">
        <section class="card">
            <h2><?= htmlspecialchars($selectedResume['title']) ?></h2>
            <?php if ($selectedResume['summary']): ?>
                <p><?= nl2br(htmlspecialchars($selectedResume['summary'])) ?></p>
            <?php endif; ?>
            <form method="post" style="display:inline;">
                <input type="hidden" name="action" value="delete_resume">
                <input type="hidden" name="resume_id" value="<?= (int) $selectedId ?>">
                <button type="submit" class="btn btn-danger"
                        onclick="return confirm('Delete this resume?')">Delete Resume</button>
            </form>
        </section>

        <!-- Education -->
        <section class="card">
            <h2>Education</h2>
            <?php foreach ($education as $edu): ?>
                <div class="entry">
                    <strong><?= htmlspecialchars($edu['institution']) ?></strong> –
                    <?= htmlspecialchars($edu['degree']) ?>
                    <?php if ($edu['field_of_study']): ?>(<?= htmlspecialchars($edu['field_of_study']) ?>)<?php endif; ?>
                    <span class="dates"><?= $edu['start_year'] ?> – <?= $edu['end_year'] ?: 'Present' ?></span>
                </div>
            <?php endforeach; ?>
            <form method="post">
                <input type="hidden" name="action" value="add_education">
                <input type="hidden" name="resume_id" value="<?= (int) $selectedId ?>">
                <label>Institution <input type="text" name="institution" required></label>
                <label>Degree <input type="text" name="degree" required></label>
                <label>Field of Study <input type="text" name="field_of_study"></label>
                <label>Start Year <input type="number" name="start_year" min="1950" max="2099"></label>
                <label>End Year <input type="number" name="end_year" min="1950" max="2099"></label>
                <label>Description <textarea name="edu_description" rows="2"></textarea></label>
                <button type="submit" class="btn btn-secondary">Add Education</button>
            </form>
        </section>

        <!-- Experience -->
        <section class="card">
            <h2>Work Experience</h2>
            <?php foreach ($experience as $exp): ?>
                <div class="entry">
                    <strong><?= htmlspecialchars($exp['position']) ?></strong> at
                    <?= htmlspecialchars($exp['company']) ?>
                    <span class="dates"><?= $exp['start_date'] ?> – <?= $exp['is_current'] ? 'Present' : $exp['end_date'] ?></span>
                    <?php if ($exp['description']): ?><p><?= nl2br(htmlspecialchars($exp['description'])) ?></p><?php endif; ?>
                </div>
            <?php endforeach; ?>
            <form method="post">
                <input type="hidden" name="action" value="add_experience">
                <input type="hidden" name="resume_id" value="<?= (int) $selectedId ?>">
                <label>Company <input type="text" name="company" required></label>
                <label>Position <input type="text" name="position" required></label>
                <label>Start Date <input type="date" name="start_date"></label>
                <label>End Date <input type="date" name="end_date"></label>
                <label><input type="checkbox" name="is_current"> Current Job</label>
                <label>Description <textarea name="exp_description" rows="3"></textarea></label>
                <button type="submit" class="btn btn-secondary">Add Experience</button>
            </form>
        </section>

        <!-- Skills -->
        <section class="card">
            <h2>Skills</h2>
            <ul class="skill-list">
                <?php foreach ($skills as $skill): ?>
                    <li><?= htmlspecialchars($skill['name']) ?> <span class="badge"><?= htmlspecialchars($skill['level']) ?></span></li>
                <?php endforeach; ?>
            </ul>
            <form method="post">
                <input type="hidden" name="action" value="add_skill">
                <input type="hidden" name="resume_id" value="<?= (int) $selectedId ?>">
                <label>Skill Name <input type="text" name="skill_name" required></label>
                <label>Level
                    <select name="skill_level">
                        <option value="beginner">Beginner</option>
                        <option value="intermediate" selected>Intermediate</option>
                        <option value="advanced">Advanced</option>
                        <option value="expert">Expert</option>
                    </select>
                </label>
                <button type="submit" class="btn btn-secondary">Add Skill</button>
            </form>
        </section>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</main>
</body>
</html>
