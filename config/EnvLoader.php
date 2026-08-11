<?php
/**
 * Environment Configuration Loader
 * Corporate Setup Limited - Internal CRM
 */

class EnvLoader {
    private static $instance = null;
    private static $env = [];

    private function __construct() {
        $this->loadEnv();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function loadEnv() {
        $envFile = __DIR__ . '/../.env';

        if (!file_exists($envFile)) {
            // Use config.php defaults if .env doesn't exist
            return;
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            if (strpos($line, '=') === false || strpos($line, '#') === 0) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Remove quotes if present
            if ((strpos($value, '"') === 0 && strrpos($value, '"') === strlen($value) - 1) ||
                (strpos($value, "'") === 0 && strrpos($value, "'") === strlen($value) - 1)) {
                $value = substr($value, 1, -1);
            }

            self::$env[$key] = $value;
        }
    }

    public static function get($key, $default = null) {
        return self::$env[$key] ?? $_ENV[$key] ?? getenv($key) ?: $default;
    }

    public static function getAll() {
        return self::$env;
    }
}

// Auto-initialize on load
EnvLoader::getInstance();
