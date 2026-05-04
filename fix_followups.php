<?php
require 'config/db.php';

echo "=== follow_ups table structure ===" . PHP_EOL;
$rows = $pdo->query('DESCRIBE follow_ups')->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    echo $r['Field'] . ' | ' . $r['Type'] . PHP_EOL;
}

// Drop and recreate with correct columns
echo PHP_EOL . "Dropping and recreating follow_ups with correct columns..." . PHP_EOL;
$pdo->exec("DROP TABLE IF EXISTS follow_ups");
$pdo->exec("
    CREATE TABLE follow_ups (
        id INT AUTO_INCREMENT PRIMARY KEY,
        patient_id INT NOT NULL,
        doctor_id INT NULL,
        followup_date DATE NOT NULL,
        notes TEXT,
        status VARCHAR(50) NOT NULL DEFAULT 'Pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");
echo "follow_ups recreated OK!" . PHP_EOL;

// Verify
$rows = $pdo->query('DESCRIBE follow_ups')->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    echo $r['Field'] . ' | ' . $r['Type'] . PHP_EOL;
}
