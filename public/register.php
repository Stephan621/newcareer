<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/src/helpers/functions.php';
require_once dirname(__DIR__) . '/src/helpers/auth.php';
require_once dirname(__DIR__) . '/src/models/User.php';

if (isLoggedIn()) {
    redirect('dashboard.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        $username        = sanitize($_POST['username']         ?? '');
        $email           = sanitize($_POST['email']            ?? '');
        $password        = $_POST['password']         ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';

        // Validation
        if ($username === '') {
            $errors[] = 'Username is required.';
        } elseif (strlen($username) < 3 || strlen($username) > 50) {
            $errors[] = 'Username must be between 3 and 50 characters.';
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $errors[] = 'Username may only contain letters, numbers, and underscores.';
        }

        if ($email === '') {
            $errors[] = 'Email address is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }

        if ($password === '') {
            $errors[] = 'Password is required.';
        } elseif (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long.';
        } elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter and one number.';
        }

        if ($password !== $passwordConfirm) {
            $errors[] = 'Passwords do not match.';
        }

        if (empty($errors)) {
            $userModel = new User();

            if ($userModel->emailExists($email)) {
                $errors[] = 'An account with that email address already exists.';
            } elseif ($userModel->usernameExists($username)) {
                $errors[] = 'That username is already taken.';
            } else {
                $id = $userModel->create($username, $email, $password);
                if ($id > 0) {
                    $user = $userModel->findById($id);
                    login($user);
                    flashMessage('success', 'Account created successfully! Welcome to ' . APP_NAME . '.');
                    redirect('dashboard.php');
                } else {
                    $errors[] = 'Registration failed. Please try again.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account – <?= APP_NAME ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container nav-container">
            <a href="index.php" class="nav-logo"><?= APP_NAME ?></a>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="jobs/index.php">Jobs</a></li>
                <li><a href="login.php" class="btn btn-outline btn-sm">Sign In</a></li>
            </ul>
        </div>
    </nav>

    <main class="auth-wrapper">
        <div class="auth-card">
            <h1 class="auth-title">Create Your Account</h1>
            <p class="auth-subtitle">Start your career journey with <?= APP_NAME ?></p>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <ul class="mb-0">
                        <?php foreach ($errors as $e): ?>
                            <li><?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8') ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="register.php" novalidate class="form" id="registerForm">
                <?= csrfField() ?>

                <div class="form-group">
                    <label for="username" class="form-label">Username</label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        class="form-control"
                        value="<?= htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        required
                        minlength="3"
                        maxlength="50"
                        pattern="[a-zA-Z0-9_]+"
                        autocomplete="username"
                        placeholder="john_doe"
                    >
                </div>

                <div class="form-group">
                    <label for="email" class="form-label">Email Address</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-control"
                        value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        required
                        autocomplete="email"
                        placeholder="you@example.com"
                    >
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control"
                            required
                            minlength="8"
                            autocomplete="new-password"
                            placeholder="Min. 8 characters"
                        >
                        <button type="button" class="btn-toggle-pw" data-target="password" aria-label="Toggle password">👁</button>
                    </div>
                    <small class="form-hint">At least 8 characters, one uppercase letter, and one number.</small>
                </div>

                <div class="form-group">
                    <label for="password_confirm" class="form-label">Confirm Password</label>
                    <div class="input-group">
                        <input
                            type="password"
                            id="password_confirm"
                            name="password_confirm"
                            class="form-control"
                            required
                            autocomplete="new-password"
                            placeholder="Repeat password"
                        >
                        <button type="button" class="btn-toggle-pw" data-target="password_confirm" aria-label="Toggle password">👁</button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Create Account</button>
            </form>

            <p class="auth-footer">
                Already have an account? <a href="login.php">Sign in</a>
            </p>
        </div>
    </main>

    <script src="../assets/js/main.js"></script>
</body>
</html>
