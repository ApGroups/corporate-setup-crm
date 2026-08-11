<?php
/**
 * One-off DB connectivity check (CLI).
 */
require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getInstance();
    $tables = $db->fetchAll('SHOW TABLES');
    echo 'DB_OK tables=' . count($tables) . PHP_EOL;
    foreach ($tables as $row) {
        echo '- ' . array_values($row)[0] . PHP_EOL;
    }
} catch (Throwable $e) {
    fwrite(STDERR, 'DB_FAIL: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
