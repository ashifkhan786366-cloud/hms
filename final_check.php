<?php
require 'config/db.php';

echo "=== FINAL SYSTEM CHECK ===" . PHP_EOL . PHP_EOL;

$all_ok = true;
$checks = [
    // Table checks
    ['SELECT COUNT(*) FROM follow_ups WHERE followup_date = CURDATE() AND status = "Pending"', 'Dashboard follow-ups query'],
    ['SELECT COUNT(*) FROM appointments WHERE visit_date = CURDATE()', 'Dashboard OPD query'],
    ['SELECT COUNT(*) FROM notifications WHERE is_read = 0', 'Notification bell query'],
    ['SELECT COUNT(*) FROM audit_log', 'Audit log query'],
    ['SELECT COUNT(*) FROM prescriptions', 'Prescriptions query'],
    ['SELECT COUNT(*) FROM lab_orders', 'Lab orders query'],
    ['SELECT COUNT(*) FROM transactions', 'Transactions query'],
];

foreach ($checks as $c) {
    try {
        $pdo->query($c[0])->fetchColumn();
        echo "  OK  | " . $c[1] . PHP_EOL;
    } catch (PDOException $e) {
        echo " FAIL | " . $c[1] . " — " . $e->getMessage() . PHP_EOL;
        $all_ok = false;
    }
}

echo PHP_EOL;
if ($all_ok) {
    echo "=== ALL SYSTEMS OK — HMS IS READY ===" . PHP_EOL;
    echo "Visit: http://localhost/hms/index.php" . PHP_EOL;
    echo "Visit: http://localhost/hms/opd.php" . PHP_EOL;
} else {
    echo "=== SOME CHECKS FAILED ===" . PHP_EOL;
}
