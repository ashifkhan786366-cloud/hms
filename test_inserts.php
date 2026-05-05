<?php
require 'config/db.php';

echo "=== Testing OPD Insert ===" . PHP_EOL;

// Show appointments columns
$cols = $pdo->query('SHOW COLUMNS FROM appointments')->fetchAll(PDO::FETCH_ASSOC);
echo PHP_EOL . "appointments columns:" . PHP_EOL;
foreach ($cols as $c) echo "  " . $c['Field'] . " | " . $c['Type'] . " | Default: " . $c['Default'] . PHP_EOL;

// Show bills columns  
$cols = $pdo->query('SHOW COLUMNS FROM bills')->fetchAll(PDO::FETCH_ASSOC);
echo PHP_EOL . "bills columns:" . PHP_EOL;
foreach ($cols as $c) echo "  " . $c['Field'] . " | " . $c['Type'] . " | Default: " . $c['Default'] . PHP_EOL;

// Test OPD appointment insert
echo PHP_EOL . "Testing appointment insert..." . PHP_EOL;
try {
    $stmt = $pdo->prepare("INSERT INTO appointments (patient_id, doctor_id, visit_date, token_number, symptoms, status) VALUES (?, ?, ?, ?, ?, 'Pending')");
    $stmt->execute([1, 1, date('Y-m-d'), 99, 'Test symptoms']);
    $id = $pdo->lastInsertId();
    echo "Appointment insert OK! ID: $id" . PHP_EOL;
    $pdo->exec("DELETE FROM appointments WHERE id = $id");
    echo "Test row deleted." . PHP_EOL;
} catch (PDOException $e) {
    echo "FAILED: " . $e->getMessage() . PHP_EOL;
}

// Test bills insert  
echo PHP_EOL . "Testing bill insert..." . PHP_EOL;
try {
    $stmt = $pdo->prepare("INSERT INTO bills (bill_number, patient_id, doctor_id, bill_date, total_amount, net_amount, balance_due, paid_amount, payment_status, status, bill_type, generated_by) VALUES (?, ?, ?, NOW(), ?, ?, ?, 0, 'Pending', 'Pending', 'OPD', ?)");
    $stmt->execute(['TEST-99999', 1, 1, 100, 100, 100, 1]);
    $id = $pdo->lastInsertId();
    echo "Bill insert OK! ID: $id" . PHP_EOL;
    $pdo->exec("DELETE FROM bills WHERE id = $id");
    echo "Test row deleted." . PHP_EOL;
} catch (PDOException $e) {
    echo "FAILED: " . $e->getMessage() . PHP_EOL;
}

echo PHP_EOL . "=== All checks done ===" . PHP_EOL;
