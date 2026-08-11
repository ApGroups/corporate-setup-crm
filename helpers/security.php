<?php
/**
 * Security Helper Functions
 * Corporate Setup Limited - Internal CRM
 */

class Security {
    public static function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function validateCSRFToken($token) {
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }

        // Reject arrays/objects and normalize token value
        if (!is_string($token)) {
            return false;
        }

        $token = trim($token);
        $sessionToken = (string) $_SESSION['csrf_token'];

        if ($token === '' || $sessionToken === '') {
            return false;
        }

        return hash_equals($sessionToken, $token);
    }

    public static function getCSRFField() {
        return '<input type="hidden" name="csrf_token" value="' . self::generateCSRFToken() . '">';
    }

    public static function sanitizeInput($input) {
        if (is_array($input)) {
            return array_map([self::class, 'sanitizeInput'], $input);
        }

        $input = trim($input);
        $input = stripslashes($input);
        $input = htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return $input;
    }

    public static function validateFileUpload($file) {
        $errors = [];

        if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
            $errors[] = 'No file uploaded';
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Upload error: ' . self::getUploadError($file['error']);
        }

        if ($file['size'] > MAX_FILE_SIZE) {
            $errors[] = 'File size exceeds maximum allowed (' . (MAX_FILE_SIZE / 1048576) . 'MB)';
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ALLOWED_EXTENSIONS)) {
            $errors[] = 'File type not allowed. Allowed: ' . implode(', ', ALLOWED_EXTENSIONS);
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowedMimes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'image/jpeg',
            'image/png',
            'image/gif'
        ];

        if (!in_array($mimeType, $allowedMimes)) {
            $errors[] = 'Invalid file type detected';
        }

        return ['valid' => empty($errors), 'errors' => $errors];
    }

    private static function getUploadError($code) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension',
        ];

        return $errors[$code] ?? 'Unknown error';
    }

    public static function hasXSS($input) {
        $patterns = [
            '/<script\b[^>]*>(.*?)<\/script>/is',
            '/javascript:/i',
            '/on\w+\s*=/i',
            '/<(iframe|frame|object|embed)/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }

        return false;
    }

    public static function hasSQLInjection($input) {
        $patterns = [
            '/(\bOR\b|\bAND\b)\s+[\'"]?\d*[\'"]?\s*[=;]/i',
            '/UNION\s+SELECT/i',
            '/DROP\s+TABLE/i',
            '/INSERT\s+INTO/i',
            '/DELETE\s+FROM/i',
            '/--$/',
            '/\/\*/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }

        return false;
    }

    public static function generateSecurePassword($length = 12) {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%^&*';
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $password;
    }

    public static function hashPassword($password) {
        return password_hash($password, HASH_ALGO);
    }

    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
}

function csrf_field() {
    return Security::getCSRFField();
}
