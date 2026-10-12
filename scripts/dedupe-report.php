<?php
/**
 * Dry-run report of exact RC collisions and identical elite start dates.
 * --apply reassigns child rows from newer RC duplicates onto the oldest company, then marks the newer company inactive.
 * Name-only matches are printed and never merged.
 *
 * Run: C:\xampp\php\php.exe scripts\dedupe-report.php [--apply]
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only\n");
    exit(1);
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$apply = in_array('--apply', $argv ?? [], true);
$db = Database::getInstance();

echo $apply ? "APPLY mode: RC duplicates will be reassigned and marked inactive.\n" : "DRY RUN. Pass --apply to reassign exact RC duplicates.\n";

$rcGroups = $db->fetchAll(
    "SELECT rc_number, COUNT(*) AS total, GROUP_CONCAT(id ORDER BY id SEPARATOR ',') AS ids
     FROM companies
     WHERE rc_number IS NOT NULL AND TRIM(rc_number) <> ''
     GROUP BY rc_number
     HAVING total > 1
     ORDER BY rc_number"
);

echo "\nRC number collisions: " . count($rcGroups) . "\n";
foreach ($rcGroups as $group) {
    echo "  {$group['rc_number']} companies={$group['ids']}\n";
}

$eliteGroups = $db->fetchAll(
    "SELECT company_id, membership_start_date, COUNT(*) AS total, GROUP_CONCAT(id ORDER BY id SEPARATOR ',') AS ids
     FROM elite_members
     GROUP BY company_id, membership_start_date
     HAVING total > 1
     ORDER BY company_id, membership_start_date"
);

echo "\nIdentical elite start dates: " . count($eliteGroups) . "\n";
foreach ($eliteGroups as $group) {
    echo "  company {$group['company_id']} start {$group['membership_start_date']} members={$group['ids']}\n";
}

$nameGroups = $db->fetchAll(
    "SELECT company_name, COUNT(*) AS total, GROUP_CONCAT(id ORDER BY id SEPARATOR ',') AS ids
     FROM companies
     GROUP BY company_name
     HAVING total > 1
     ORDER BY company_name"
);

echo "\nName-only matches (not merged): " . count($nameGroups) . "\n";
foreach ($nameGroups as $group) {
    echo "  {$group['company_name']} companies={$group['ids']}\n";
}

if (!$apply) {
    exit(0);
}

$childTables = [
    'registrations' => 'company_id',
    'documents' => 'company_id',
    'tasks' => 'related_company_id',
    'communications' => 'company_id',
];

foreach ($rcGroups as $group) {
    $ids = array_map('intval', explode(',', $group['ids']));
    sort($ids);
    $keepId = array_shift($ids);
    foreach ($ids as $duplicateId) {
        foreach ($childTables as $table => $column) {
            $rows = $db->fetchAll("SELECT id FROM {$table} WHERE {$column} = ?", [$duplicateId]);
            foreach ($rows as $row) {
                try {
                    $db->update($table, [$column => $keepId], 'id = :id', ['id' => $row['id']]);
                } catch (Exception $e) {
                    echo "  skipped {$table} #{$row['id']}: {$e->getMessage()}\n";
                }
            }
        }
        $db->update('companies', ['status' => 'inactive', 'updated_at' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $duplicateId]);
        echo "  reassigned company {$duplicateId} onto {$keepId} and set status inactive\n";
    }
}

echo "Apply finished. Name-only matches were left unchanged.\n";
exit(0);
