<?php
/**
 * Forgot Password Page
 * Corporate Setup Limited - Internal CRM
 */

require_once 'config/config.php';

$auth = new Auth();

// Redirect if already logged in
if ($auth->isLoggedIn()) {
    redirect('index.php');
}

$error = '';
$success = '';
$email = '';
$token = $_GET['token'] ?? null;

// Password Reset Form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    if (!isset($_POST['csrf_token']) || !Security::validateCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid security token.';
    } else {
        $token = sanitize($_POST['token'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($token)) {
            $error = 'Invalid reset link.';
        } elseif ($password !== $confirmPassword) {
            $error = 'Passwords do not match.';
        } elseif (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters.';
        } else {
            $result = $auth->resetPassword($token, $password);

            if ($result['success']) {
                flashMessage('success', 'Your password has been reset successfully. Please log in.');
                redirect('login.php');
            } else {
                $error = $result['error'];
            }
        }
    }
}

// Request Reset Form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_reset'])) {
    if (!isset($_POST['csrf_token']) || !Security::validateCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid security token.';
    } else {
        $email = sanitize($_POST['email'] ?? '');

        if (empty($email)) {
            $error = 'Please enter your email address.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            $result = $auth->createPasswordReset($email);

            // Always show success message to prevent email enumeration
            $success = 'If an account exists with that email, you will receive a password reset link shortly.';

            if ($result['success']) {
                // In production, send an email with the reset link
                // For now, we'll just log it
                // $resetLink = APP_URL . '/forgot-password.php?token=' . $result['token'];
                // mail($email, 'Password Reset', "Click here to reset: $resetLink");
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
    <title>Forgot Password - <?= APP_NAME ?></title>
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

                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <div class="alert-content"><?= sanitize($success) ?></div>
                    </div>
                <?php endif; ?>

                <?php if ($token): ?>
                    <!-- Reset Password Form -->
                    <h3 style="margin-bottom: 1.5rem;">Reset Your Password</h3>
                    <form method="POST" action="">
                        <?= csrf_field() ?>
                        <input type="hidden" name="token" value="<?= sanitize($token) ?>">

                        <div class="form-group">
                            <label class="form-label required" for="password">New Password</label>
                            <input type="password" id="password" name="password" class="form-control"
                                   placeholder="Enter new password (min 8 characters)" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label required" for="confirm_password">Confirm Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control"
                                   placeholder="Confirm new password" required>
                        </div>

                        <button type="submit" name="reset_password" class="btn btn-primary btn-block btn-lg">
                            <i class="fas fa-key"></i>
                            Reset Password
                        </button>
                    </form>
                <?php else: ?>
                    <!-- Request Reset Form -->
                    <h3 style="margin-bottom: 1.5rem;">Forgot Your Password?</h3>
                    <p style="color: var(--dark-grey); margin-bottom: 1.5rem; font-size: 0.875rem;">
                        Enter your email address and we'll send you a link to reset your password.
                    </p>
                    <form method="POST" action="">
                        <?= csrf_field() ?>

                        <div class="form-group">
                            <label class="form-label required" for="email">Email Address</label>
                            <input type="email" id="email" name="email" class="form-control"
                                   value="<?= sanitize($email) ?>" placeholder="Enter your email" required autofocus>
                        </div>

                        <button type="submit" name="request_reset" class="btn btn-primary btn-block btn-lg">
                            <i class="fas fa-paper-plane"></i>
                            Send Reset Link
                        </button>
                    </form>
                <?php endif; ?>

                <div style="margin-top: 1.5rem; text-align: center;">
                    <a href="login.php"><i class="fas fa-arrow-left"></i> Back to Login</a>
                </div>

                <div style="margin-top: 1.5rem; text-align: center; color: var(--dark-grey); font-size: 0.75rem;">
                    <p>Corporate Setup Limited &copy; <?= date('Y') ?></p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
