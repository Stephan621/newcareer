<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/src/helpers/functions.php';
require_once dirname(__DIR__, 2) . '/src/helpers/auth.php';
require_once dirname(__DIR__, 2) . '/src/models/Resume.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}

if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
    flashMessage('error', 'Invalid security token.');
    redirect('index.php');
}

$user        = getCurrentUser();
$resumeModel = new Resume();
$id          = (int) ($_POST['id'] ?? 0);

if ($id <= 0) {
    redirect('index.php');
}

$resume = $resumeModel->findById($id);

if (!$resume || (int) $resume['user_id'] !== $user['id']) {
    flashMessage('error', 'Resume not found or access denied.');
    redirect('index.php');
}

$resumeModel->delete($id);
flashMessage('success', 'Resume deleted successfully.');
redirect('index.php');
