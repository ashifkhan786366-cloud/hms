<?php
require_once 'config/db.php';

echo "Refining Schema for Phase 3...<br>";

// Tab 5: ipd_io_chart
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS ipd_io_chart (
        id INT AUTO_INCREMENT PRIMARY KEY,
        admission_id INT NOT NULL,
        record_date DATE NOT NULL,
        shift VARCHAR(50),
        oral_ml INT DEFAULT 0,
        iv_ml INT DEFAULT 0,
        other_intake_ml INT DEFAULT 0,
        urine_ml INT DEFAULT 0,
        vomit_ml INT DEFAULT 0,
        ngt_ml INT DEFAULT 0,
        drain_ml INT DEFAULT 0,
        other_output_ml INT DEFAULT 0,
        recorded_by VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "ipd_io_chart checked/created.<br>";
} catch (PDOException $e) { echo "ipd_io_chart error: " . $e->getMessage() . "<br>"; }

// Tab 6: ipd_doctor_notes
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS ipd_doctor_notes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        admission_id INT NOT NULL,
        note_datetime DATETIME NOT NULL,
        doctor_name VARCHAR(100),
        note_type VARCHAR(100),
        subjective TEXT,
        objective TEXT,
        assessment TEXT,
        plan TEXT,
        next_review_date DATE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "ipd_doctor_notes checked/created.<br>";
} catch (PDOException $e) { echo "ipd_doctor_notes error: " . $e->getMessage() . "<br>"; }

// Tab 7: ipd_nursing_notes
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS ipd_nursing_notes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        admission_id INT NOT NULL,
        note_datetime DATETIME NOT NULL,
        nurse_name VARCHAR(100),
        shift VARCHAR(50),
        patient_condition VARCHAR(50),
        consciousness VARCHAR(50),
        observations TEXT,
        actions_taken TEXT,
        patient_complaints TEXT,
        bp VARCHAR(20),
        hr INT,
        temp DECIMAL(4,1),
        spo2 INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "ipd_nursing_notes checked/created.<br>";
} catch (PDOException $e) { echo "ipd_nursing_notes error: " . $e->getMessage() . "<br>"; }

echo "Phase 3 Schema Complete.";
?>
