<?php
require_once 'config/db.php';

echo "Refining Schema for Phase 2...<br>";

// 1. ipd_medications - Ensure 'notes' column exists
try {
    $pdo->exec("ALTER TABLE ipd_medications ADD COLUMN notes TEXT AFTER prescribed_by");
    echo "Added 'notes' to ipd_medications.<br>";
} catch (PDOException $e) {
    echo "ipd_medications 'notes' check: " . $e->getMessage() . "<br>";
}

// 2. Rename ipd_medication_doses to ipd_med_given and adjust columns
try {
    // Check if ipd_medication_doses exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'ipd_medication_doses'");
    if ($stmt->rowCount() > 0) {
        $pdo->exec("RENAME TABLE ipd_medication_doses TO ipd_med_given");
        echo "Renamed ipd_medication_doses to ipd_med_given.<br>";
    }
} catch (PDOException $e) {
    echo "Rename check: " . $e->getMessage() . "<br>";
}

// Ensure ipd_med_given has 'remarks' column
try {
    $pdo->exec("ALTER TABLE ipd_med_given ADD COLUMN remarks TEXT AFTER given_by");
    echo "Added 'remarks' to ipd_med_given.<br>";
} catch (PDOException $e) {
    echo "ipd_med_given 'remarks' check: " . $e->getMessage() . "<br>";
}

// Ensure ipd_iv_fluids has 'bottle_no' and other fields matched
try {
    $pdo->exec("ALTER TABLE ipd_iv_fluids CHANGE bottle_number bottle_no VARCHAR(50)");
    echo "Renamed bottle_number to bottle_no.<br>";
} catch (PDOException $e) {
    echo "ipd_iv_fluids check: " . $e->getMessage() . "<br>";
}

echo "Schema Refinement Complete.";
?>
