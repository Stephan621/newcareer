<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/User.php';

if (isset($_SESSION['user'])) {
    header('Location: /');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';

    if ($name === '' || $email === '' || $password === '') {
        $errors[] = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    } else {
        $userModel = new User();
        if ($userModel->findByEmail($email)) {
            $errors[] = 'An account with this email already exists.';
        } else {
            $userId = $userModel->create($name, $email, $password);
            session_regenerate_id(true);
            $_SESSION['user'] = ['id' => $userId, 'name' => $name, 'role' => 'user'];
            header('Location: /');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register – <?= htmlspecialchars(APP_NAME) ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<nav class="navbar">
    <a class="brand" href="/"><?= htmlspecialchars(APP_NAME) ?></a>
</nav>
<main class="container form-page">
    <h1>Create Account</h1>
    <?php foreach ($errors as $e): ?>
        <p class="error"><?= htmlspecialchars($e) ?></p>
    <?php endforeach; ?>
    <form method="post" action="/register.php">
        <label>Name <input type="text" name="name" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"></label>
        <label>Email <input type="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"></label>
        <label>Password <input type="password" name="password" required minlength="8"></label>
        <label>Confirm Password <input type="password" name="confirm" required></label>
        <button type="submit" class="btn btn-primary">Register</button>
    </form>
    <p>Already have an account? <a href="/login.php">Login</a></p>
</main>
</body>
</html>
