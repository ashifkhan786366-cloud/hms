<?php
require 'config/db.php';

$all_ok = true;

// Test appointments table
$cols = $pdo->query('SHOW COLUMNS FROM appointments')->fetchAll(PDO::FETCH_COLUMN);
$has = in_array('status', $cols);
echo 'appointments.status: ' . ($has ? 'EXISTS OK' : 'MISSING!') . PHP_EOL;
if (!$has) $all_ok = false;

// Test bills table
$cols = $pdo->query('SHOW COLUMNS FROM bills')->fetchAll(PDO::FETCH_COLUMN);
$has1 = in_array('status', $cols);
$has2 = in_array('generated_by', $cols);
echo 'bills.status: ' . ($has1 ? 'EXISTS OK' : 'MISSING!') . PHP_EOL;
echo 'bills.generated_by: ' . ($has2 ? 'EXISTS OK' : 'MISSING!') . PHP_EOL;
if (!$has1 || !$has2) $all_ok = false;

// Test follow_ups table
$cols = $pdo->query('SHOW COLUMNS FROM follow_ups')->fetchAll(PDO::FETCH_COLUMN);
$has3 = in_array('followup_date', $cols);
$has4 = in_array('status', $cols);
echo 'follow_ups.followup_date: ' . ($has3 ? 'EXISTS OK' : 'MISSING!') . PHP_EOL;
echo 'follow_ups.status: ' . ($has4 ? 'EXISTS OK' : 'MISSING!') . PHP_EOL;
if (!$has3 || !$has4) $all_ok = false;

// Test audit_log
$cols = $pdo->query('SHOW COLUMNS FROM audit_log')->fetchAll(PDO::FETCH_COLUMN);
$cnt = count($cols);
echo 'audit_log table: ' . ($cnt > 0 ? 'EXISTS OK ('.$cnt.' columns)' : 'MISSING!') . PHP_EOL;
if ($cnt == 0) $all_ok = false;

// Test prescriptions
$cols = $pdo->query('SHOW COLUMNS FROM prescriptions')->fetchAll(PDO::FETCH_COLUMN);
echo 'prescriptions table: ' . (count($cols) > 0 ? 'EXISTS OK' : 'MISSING!') . PHP_EOL;

// Test notifications
$cols = $pdo->query('SHOW COLUMNS FROM notifications')->fetchAll(PDO::FETCH_COLUMN);
echo 'notifications table: ' . (count($cols) > 0 ? 'EXISTS OK' : 'MISSING!') . PHP_EOL;

// Test lab_orders
$cols = $pdo->query('SHOW COLUMNS FROM lab_orders')->fetchAll(PDO::FETCH_COLUMN);
echo 'lab_orders table: ' . (count($cols) > 0 ? 'EXISTS OK' : 'MISSING!') . PHP_EOL;

echo PHP_EOL;
echo ($all_ok ? '=== ALL CHECKS PASSED - System Ready ===' : '=== SOME CHECKS FAILED ===') . PHP_EOL;
