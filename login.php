<?php
/**
 * Login Page
 * Corporate Setup Limited - Internal CRM
 */

require_once 'config/config.php';

$auth = new Auth();

// Redirect if already logged in
if ($auth->isLoggedIn()) {
    redirect('index.php');
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !Security::validateCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $email = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);

        if (empty($email) || empty($password)) {
            $error = 'Please enter your email and password.';
        } else {
            $result = $auth->login($email, $password, $remember);

            if ($result['success']) {
                $redirect = $_GET['redirect'] ?? 'index.php';
                // Prevent open redirects — allow only relative in-app paths
                if (!is_string($redirect) || $redirect === '' || preg_match('#^(https?:)?//#i', $redirect) || strpos($redirect, '..') !== false) {
                    $redirect = 'index.php';
                }
                $redirect = ltrim($redirect, '/');
                redirect($redirect);
            } else {
                $error = $result['error'];
            }
        }
    }
}

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-container">
            <div class="auth-card">
                <div class="auth-logo">
                    <h1><i class="fas fa-building"></i> Corporate <span>Setup</span></h1>
                    <p>Internal CRM System</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        <div class="alert-content"><?= sanitize($error) ?></div>
                    </div>
                <?php endif; ?>

                <?php if ($flash): ?>
                    <div class="alert alert-<?= $flash['type'] ?>">
                        <i class="fas fa-info-circle"></i>
                        <div class="alert-content"><?= sanitize($flash['message']) ?></div>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['timeout'])): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-clock"></i>
                        <div class="alert-content">Your session has expired. Please log in again.</div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <?= csrf_field() ?>

                    <div class="form-group">
                        <label class="form-label required" for="email">Email Address</label>
                        <input type="email" id="email" name="email" class="form-control"
                               value="<?= sanitize($email) ?>" placeholder="Enter your email" required autofocus>
                    </div>

                    <div class="form-group">
                        <label class="form-label required" for="password">Password</label>
                        <div style="position: relative;">
                            <input type="password" id="password" name="password" class="form-control"
                                   placeholder="Enter your password" required>
                            <button type="button" class="password-toggle" onclick="togglePassword()" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--dark-grey); cursor: pointer;">
                                <i class="fas fa-eye" id="toggleIcon"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-group" style="display: flex; justify-content: space-between; align-items: center;">
                        <label class="form-check">
                            <input type="checkbox" name="remember">
                            <span>Remember me</span>
                        </label>
                        <a href="forgot-password.php" style="font-size: 0.875rem;">Forgot password?</a>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block btn-lg">
                        <i class="fas fa-sign-in-alt"></i>
                        Sign In
                    </button>
                </form>

                <div style="margin-top: 1.5rem; text-align: center; color: var(--dark-grey); font-size: 0.75rem;">
                    <p>Corporate Setup Limited &copy; <?= date('Y') ?></p>
                    <p>Internal use only. Unauthorized access is prohibited.</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>
