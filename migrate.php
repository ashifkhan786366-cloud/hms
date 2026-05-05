<?php
/**
 * migrate.php - Run once to set up all tables
 * Visit: https://your-app.railway.app/migrate.php
 */
require_once __DIR__ . '/config/db.php';

header('Content-Type: text/html; charset=utf-8');
echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>HMS Migration</title>';
echo '<style>body{font-family:Arial,sans-serif;max-width:900px;margin:40px auto;padding:20px;background:#f5f5f5;}';
echo '.ok{color:#2e7d32;} .err{color:#c62828;font-weight:bold;} h2{color:#1a237e;}';
echo '.card{background:#fff;padding:20px;border-radius:8px;box-shadow:0 2px 6px rgba(0,0,0,.1);margin:15px 0;}';
echo '.done{background:#e8f5e9;border:2px solid #4caf50;padding:16px;border-radius:8px;}';
echo '</style></head><body>';
echo '<h2>&#x1F3E5; HMS Database Migration</h2>';
echo '<div class="card">';

$db = $pdo->query("SELECT DATABASE()")->fetchColumn();
echo "<p>Database: <strong>$db</strong></p>";

$sqlFile = __DIR__ . '/database_setup.sql';
if (!file_exists($sqlFile)) {
    die('<p class="err">ERROR: database_setup.sql not found!</p></div></body></html>');
}

$sql = file_get_contents($sqlFile);

// Split on semicolons but skip empty statements
$statements = array_filter(
    array_map('trim', explode(';', $sql)),
    fn($s) => strlen($s) > 5
);

$ok = 0; $fail = 0;
foreach ($statements as $stmt) {
    try {
        $pdo->exec($stmt);
        $ok++;
    } catch (PDOException $e) {
        $msg = $e->getMessage();
        // Ignore "already exists" errors
        if (strpos($msg, 'already exists') === false && strpos($msg, 'Duplicate') === false) {
            echo "<p class='err'>&#x274C; " . htmlspecialchars(substr($stmt, 0, 80)) . "...<br><small>" . htmlspecialchars($msg) . "</small></p>";
            $fail++;
        } else {
            $ok++;
        }
    }
}

echo "<p class='ok'>&#x2705; <strong>$ok</strong> statements executed successfully.</p>";
if ($fail > 0) echo "<p class='err'>&#x26A0;&#xFE0F; <strong>$fail</strong> statements failed (see above).</p>";

// Also add missing columns to existing tables
$migrations = [
    ['bills', 'balance_due', 'DECIMAL(10,2) DEFAULT 0'],
    ['bills', 'bill_type', "VARCHAR(20) DEFAULT 'OPD'"],
    ['bills', 'payment_mode_cash', 'DECIMAL(10,2) DEFAULT 0'],
    ['bills', 'payment_mode_upi', 'DECIMAL(10,2) DEFAULT 0'],
    ['bills', 'discount_type', "VARCHAR(20) DEFAULT 'amount'"],
    ['bills', 'discount_percent', 'DECIMAL(5,2) DEFAULT 0'],
    ['bills', 'modified_at', 'DATETIME'],
    ['bills', 'modified_by', 'INT DEFAULT NULL'],
    ['bills', 'last_edited_at', 'DATETIME'],
    ['bill_items', 'report_status', 'VARCHAR(20) DEFAULT NULL'],
    ['bill_items', 'item_type', "VARCHAR(50) DEFAULT 'General'"],
    ['bill_items', 'discount_percent', 'DECIMAL(5,2) DEFAULT 0'],
    ['bill_items', 'lab_result', 'TEXT'],
    ['bill_items', 'rate', 'DECIMAL(10,2) DEFAULT 0'],
    ['services', 'name', 'VARCHAR(100) DEFAULT NULL'],
    ['services', 'rate', 'DECIMAL(10,2) DEFAULT 0'],
    ['services', 'status', 'TINYINT(1) DEFAULT 1'],
    ['services', 'category', 'VARCHAR(100) DEFAULT NULL'],
    ['services', 'tax_percent', 'DECIMAL(5,2) DEFAULT 0'],
    ['users', 'status', 'TINYINT(1) DEFAULT 1'],
    ['appointments', 'status', "enum('Pending','Completed','Cancelled') DEFAULT 'Pending'"],
    ['ipd_admissions', 'bed_number', 'VARCHAR(50) DEFAULT NULL'],
    ['ipd_admissions', 'ward_type', "VARCHAR(50) DEFAULT 'General'"],
];

echo "<hr><p><strong>Checking columns...</strong></p>";
$dbName = $db;
$colOk = 0;
foreach ($migrations as [$table, $col, $def]) {
    $exists = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=? AND TABLE_NAME=? AND COLUMN_NAME=?");
    $exists->execute([$dbName, $table, $col]);
    if (!$exists->fetchColumn()) {
        try {
            $pdo->exec("ALTER TABLE `$table` ADD COLUMN `$col` $def");
            echo "<p class='ok'>&#x2705; Added: <strong>$table.$col</strong></p>";
            $colOk++;
        } catch (PDOException $e) {
            $em = $e->getMessage();
            if (strpos($em, 'Duplicate') === false) {
                echo "<p class='err'>&#x274C; $table.$col: " . htmlspecialchars($em) . "</p>";
            }
        }
    }
}
if ($colOk == 0) echo "<p class='ok'>&#x2705; All columns already exist.</p>";

echo '</div>';
echo '<div class="done"><h3>&#x2705; Migration Complete!</h3>';
echo '<p>You can now <a href="index.php">go to the dashboard</a>.</p></div>';
echo '</body></html>';
?>
