<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/src/helpers/functions.php';
require_once dirname(__DIR__) . '/src/helpers/auth.php';
require_once dirname(__DIR__) . '/src/models/User.php';

// Already logged in – go to dashboard
if (isLoggedIn()) {
    redirect('dashboard.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        $email    = sanitize($_POST['email']    ?? '');
        $password = $_POST['password'] ?? '';

        if ($email === '') {
            $errors[] = 'Email address is required.';
        }
        if ($password === '') {
            $errors[] = 'Password is required.';
        }

        if (empty($errors)) {
            $userModel = new User();
            $user      = $userModel->authenticate($email, $password);

            if ($user === null) {
                $errors[] = 'Invalid email or password.';
            } else {
                login($user);
                flashMessage('success', 'Welcome back, ' . htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') . '!');

                $next = $_GET['next'] ?? '';
                if ($next !== '' && str_starts_with($next, '/')) {
                    redirect($next);
                }
                redirect('dashboard.php');
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
    <title>Login – <?= APP_NAME ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container nav-container">
            <a href="index.php" class="nav-logo"><?= APP_NAME ?></a>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="jobs/index.php">Jobs</a></li>
                <li><a href="register.php" class="btn btn-primary btn-sm">Register</a></li>
            </ul>
        </div>
    </nav>

    <main class="auth-wrapper">
        <div class="auth-card">
            <h1 class="auth-title">Welcome Back</h1>
            <p class="auth-subtitle">Sign in to your <?= APP_NAME ?> account</p>

            <?php renderFlash(); ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <ul class="mb-0">
                        <?php foreach ($errors as $e): ?>
                            <li><?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8') ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php" novalidate class="form" id="loginForm">
                <?= csrfField() ?>

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
                            autocomplete="current-password"
                            placeholder="••••••••"
                        >
                        <button type="button" class="btn-toggle-pw" data-target="password" aria-label="Toggle password visibility">👁</button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Sign In</button>
            </form>

            <p class="auth-footer">
                Don't have an account? <a href="register.php">Create one for free</a>
            </p>
        </div>
    </main>

    <script src="../assets/js/main.js"></script>
</body>
</html>
