<?php
/**
 * Database Configuration
 * Corporate Setup Limited - Internal CRM
 */

// Load environment if not already loaded
if (!class_exists('EnvLoader')) {
    require_once __DIR__ . '/EnvLoader.php';
}

// Database connection details from environment or defaults
$dbHost = EnvLoader::get('DB_HOST', 'localhost');
$dbPort = EnvLoader::get('DB_PORT', 3306);
$dbName = EnvLoader::get('DB_NAME', 'corpor43_crm');
$dbUser = EnvLoader::get('DB_USER', 'corpor43_corper');
$dbPass = EnvLoader::get('DB_PASS', 'Per43-crm');
$dbCharset = EnvLoader::get('DB_CHARSET', 'utf8mb4');

define('DB_HOST', $dbHost);
define('DB_PORT', $dbPort);
define('DB_NAME', $dbName);
define('DB_USER', $dbUser);
define('DB_PASS', $dbPass);
define('DB_CHARSET', $dbCharset);

class Database {
    private static $instance = null;
    private $pdo;
    private $dsn;
    private $options;

    private function __construct() {
        $this->dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $this->options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            // Work around MySQL 1615 "Prepared statement needs to be re-prepared"
            // seen on some hosts with small table_definition_cache.
            PDO::ATTR_EMULATE_PREPARES => true,
        ];

        try {
            $this->pdo = new PDO($this->dsn, DB_USER, DB_PASS, $this->options);
        } catch (PDOException $e) {
            error_log("Database Connection Failed: " . $e->getMessage());
            
            // In production, don't expose actual error details
            $userMessage = (defined('APP_ENV') && APP_ENV === 'production') 
                ? "Database connection failed. Please contact IT support."
                : "Database Connection Error: " . $e->getMessage();
            
            die($userMessage);
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->pdo;
    }

    public function query($sql, $params = []) {
        $attempt = 0;
        $maxAttempts = 3; // Initial try + retries for transient DB statement cache issues.

        while ($attempt < $maxAttempts) {
            try {
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($params);
                return $stmt;
            } catch (PDOException $e) {
                $mysqlErrorCode = isset($e->errorInfo[1]) ? intval($e->errorInfo[1]) : 0;
                $isReprepareError = $mysqlErrorCode === 1615
                    || stripos($e->getMessage(), 'needs to be re-prepared') !== false;

                if ($isReprepareError && $attempt < ($maxAttempts - 1)) {
                    $attempt++;
                    if (!$this->pdo->inTransaction()) {
                        try {
                            $this->pdo = new PDO($this->dsn, DB_USER, DB_PASS, $this->options);
                        } catch (PDOException $reconnectError) {
                            error_log('DB reconnect failed during 1615 retry: ' . $reconnectError->getMessage());
                        }
                    }
                    usleep(50000); // 50ms backoff before retry
                    continue;
                }

                error_log("SQL Error: " . $e->getMessage() . "\nQuery: " . $sql);
                throw $e;
            }
        }

        throw new PDOException('Database query failed after retry attempts');
    }

    public function fetchAll($sql, $params = []) {
        return $this->query($sql, $params)->fetchAll();
    }

    public function fetch($sql, $params = []) {
        return $this->query($sql, $params)->fetch();
    }

    public function insert($table, $data) {
        $fields = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        $sql = "INSERT INTO {$table} ({$fields}) VALUES ({$placeholders})";
        $this->query($sql, $data);
        return $this->pdo->lastInsertId();
    }

    public function update($table, $data, $where, $whereParams = []) {
        $set = [];
        foreach ($data as $key => $value) {
            $set[] = "{$key} = :{$key}";
        }
        $sql = "UPDATE {$table} SET " . implode(', ', $set) . " WHERE {$where}";
        return $this->query($sql, array_merge($data, $whereParams));
    }

    public function delete($table, $where, $params = []) {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        return $this->query($sql, $params);
    }

    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }

    public function commit() {
        return $this->pdo->commit();
    }

    public function rollBack() {
        return $this->pdo->rollBack();
    }
}
