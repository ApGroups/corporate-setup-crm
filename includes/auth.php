<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
/**
 * Authentication Functions
 * Corporate Setup Limited - Internal CRM
 */

class Auth {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function login($email, $password, $remember = false) {
        $user = $this->db->fetch(
            "SELECT u.*, r.name as role_name, r.permissions, d.name as department_name
             FROM users u
             LEFT JOIN roles r ON u.role_id = r.id
             LEFT JOIN departments d ON u.department_id = d.id
             WHERE u.email = :email AND u.status = 'active'",
            ['email' => $email]
        );

        if (!$user) {
            return ['success' => false, 'error' => 'Invalid email or password'];
        }

        if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
            return ['success' => false, 'error' => 'Account temporarily locked. Please try again later.'];
        }

        if (!password_verify($password, $user['password'])) {
            $this->handleFailedLogin($user['id']);
            return ['success' => false, 'error' => 'Invalid email or password'];
        }

        $this->createSession($user);
        $this->recordLogin($user['id'], true);

        return ['success' => true, 'user' => $this->getSafeUser($user)];
    }

    public function logout() {
        if (isset($_SESSION['user_id'])) {
            logActivity($_SESSION['user_id'], 'logout', 'User logged out');
        }

        session_unset();
        session_destroy();
        setcookie('remember_token', '', time() - 3600, '/');

        return true;
    }

    public function isLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }

    public function hasRole($role) {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
    }

    public function hasPermission($permission) {
        if ($this->hasRole('Administrator')) {
            return true;
        }
        if (!isset($_SESSION['permissions']) || !is_array($_SESSION['permissions'])) {
            return false;
        }
        if (in_array('*', $_SESSION['permissions'], true)) {
            return true;
        }
        return in_array($permission, $_SESSION['permissions'], true);
    }

    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }

        return [
            'id' => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'],
            'email' => $_SESSION['user_email'],
            'role' => $_SESSION['user_role'],
            'department' => $_SESSION['user_department'],
            'permissions' => $_SESSION['permissions'] ?? []
        ];
    }

    public function createPasswordReset($email) {
        $user = $this->db->fetch("SELECT * FROM users WHERE email = :email", ['email' => $email]);

        if (!$user) {
            return ['success' => false, 'error' => 'No account found with that email'];
        }

        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $this->db->insert('password_resets', [
            'user_id' => $user['id'],
            'token' => $token,
            'expires_at' => $expires,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        return ['success' => true, 'token' => $token, 'user' => $user];
    }

    public function resetPassword($token, $password) {
        $reset = $this->db->fetch(
            "SELECT * FROM password_resets WHERE token = :token AND used = 0 AND expires_at > NOW()",
            ['token' => $token]
        );

        if (!$reset) {
            return ['success' => false, 'error' => 'Invalid or expired reset token'];
        }

        if (strlen($password) < 8) {
            return ['success' => false, 'error' => 'Password must be at least 8 characters'];
        }

        $hashedPassword = password_hash($password, HASH_ALGO);

        $this->db->beginTransaction();

        try {
            $this->db->update('users', ['password' => $hashedPassword, 'updated_at' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $reset['user_id']]);
            $this->db->update('password_resets', ['used' => 1], 'id = :id', ['id' => $reset['id']]);
            $this->db->commit();

            return ['success' => true];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'error' => 'Failed to reset password'];
        }
    }

    public function changePassword($userId, $currentPassword, $newPassword) {
        $user = $this->db->fetch("SELECT * FROM users WHERE id = :id", ['id' => $userId]);

        if (!$user || !password_verify($currentPassword, $user['password'])) {
            return ['success' => false, 'error' => 'Current password is incorrect'];
        }

        if (strlen($newPassword) < 8) {
            return ['success' => false, 'error' => 'Password must be at least 8 characters'];
        }

        $hashedPassword = password_hash($newPassword, HASH_ALGO);

        $this->db->update('users', ['password' => $hashedPassword, 'updated_at' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $userId]);

        return ['success' => true];
    }

    private function createSession($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role_name'];
        $_SESSION['user_department'] = $user['department_name'];
        $_SESSION['user_department_id'] = $user['department_id'];
        $_SESSION['permissions'] = $user['permissions'] ? json_decode($user['permissions'], true) : [];
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();

        logActivity($user['id'], 'login', 'User logged in successfully');
    }

    private function recordLogin($userId, $success) {
        $this->db->insert('login_history', [
            'user_id' => $userId,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'cli',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'cli',
            'success' => $success ? 1 : 0,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    private function handleFailedLogin($userId) {
        $this->db->query(
            "UPDATE users SET login_attempts = login_attempts + 1 WHERE id = :id",
            ['id' => $userId]
        );

        $user = $this->db->fetch("SELECT login_attempts FROM users WHERE id = :id", ['id' => $userId]);

        if ($user && $user['login_attempts'] >= MAX_LOGIN_ATTEMPTS) {
            $lockedUntil = date('Y-m-d H:i:s', strtotime('+' . LOGIN_LOCKOUT_TIME . ' seconds'));
            $this->db->update(
                'users',
                ['locked_until' => $lockedUntil, 'login_attempts' => 0],
                'id = :id',
                ['id' => $userId]
            );
        }

        $this->recordLogin($userId, false);
    }

    private function getSafeUser($user) {
        unset($user['password']);
        return $user;
    }
}

function requireLogin() {
    $auth = new Auth();
    if (!$auth->isLoggedIn()) {
        redirect('login.php');
    }
}

function requireRole($role) {
    $auth = new Auth();
    if (!$auth->hasRole($role)) {
        http_response_code(403);
        require 'views/errors/403.php';
        exit();
    }
}

function hasPermission($permission) {
    $auth = new Auth();
    return $auth->hasPermission($permission);
}
