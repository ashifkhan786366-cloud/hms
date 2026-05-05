<?php
/**
 * Auto-fix missing columns in HMS database
 * Run this ONCE: localhost/hms/fix_columns.php
 */
require_once 'config/db.php';

$fixes = [];
$errors = [];

// Helper to run ALTER safely
function safe_alter($pdo, $sql, $label) {
    global $fixes, $errors;
    try {
        $pdo->exec($sql);
        $fixes[] = "✅ $label";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            $fixes[] = "⏭️ $label — already exists (skipped)";
        } else {
            $errors[] = "❌ $label — " . $e->getMessage();
        }
    }
}

// ─── 1. appointments table — add 'status' column ────────────────────────────
safe_alter($pdo,
    "ALTER TABLE appointments ADD COLUMN status VARCHAR(50) NOT NULL DEFAULT 'Pending'",
    "appointments.status column"
);

// ─── 2. bills table — add 'status' column ───────────────────────────────────
safe_alter($pdo,
    "ALTER TABLE bills ADD COLUMN status VARCHAR(50) NOT NULL DEFAULT 'Pending'",
    "bills.status column"
);

// ─── 3. bills table — add 'generated_by' column ─────────────────────────────
safe_alter($pdo,
    "ALTER TABLE bills ADD COLUMN generated_by INT NULL",
    "bills.generated_by column"
);

// ─── 4. follow_ups table — create if not exists ──────────────────────────────
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS follow_ups (
            id INT AUTO_INCREMENT PRIMARY KEY,
            patient_id INT NOT NULL,
            doctor_id INT NULL,
            followup_date DATE NOT NULL,
            notes TEXT,
            status VARCHAR(50) NOT NULL DEFAULT 'Pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
        )
    ");
    $fixes[] = "✅ follow_ups table — created/verified";
} catch (PDOException $e) {
    $errors[] = "❌ follow_ups table — " . $e->getMessage();
}

// ─── 5. prescriptions table — create if not exists ───────────────────────────
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS prescriptions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            patient_id INT NOT NULL,
            doctor_id INT NULL,
            appointment_id INT NULL,
            medicine_name VARCHAR(255) NOT NULL,
            dosage VARCHAR(100),
            duration VARCHAR(100),
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    $fixes[] = "✅ prescriptions table — created/verified";
} catch (PDOException $e) {
    $errors[] = "❌ prescriptions table — " . $e->getMessage();
}

// ─── 6. notifications table — create if not exists ───────────────────────────
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            type VARCHAR(100) NOT NULL,
            message TEXT NOT NULL,
            for_role VARCHAR(100) NOT NULL DEFAULT 'All',
            is_read TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    $fixes[] = "✅ notifications table — created/verified";
} catch (PDOException $e) {
    $errors[] = "❌ notifications table — " . $e->getMessage();
}

// ─── 7. lab_orders table — create if not exists ──────────────────────────────
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS lab_orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            bill_id INT NULL,
            patient_id INT NOT NULL,
            test_name VARCHAR(255) NOT NULL,
            status VARCHAR(50) NOT NULL DEFAULT 'Pending',
            done_at DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    $fixes[] = "✅ lab_orders table — created/verified";
} catch (PDOException $e) {
    $errors[] = "❌ lab_orders table — " . $e->getMessage();
}

// ─── 8. beds table — create if not exists ────────────────────────────────────
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS beds (
            id INT AUTO_INCREMENT PRIMARY KEY,
            bed_number VARCHAR(20) NOT NULL,
            ward_name VARCHAR(100) NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'Available',
            patient_id INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    $fixes[] = "✅ beds table — created/verified";
} catch (PDOException $e) {
    $errors[] = "❌ beds table — " . $e->getMessage();
}

// ─── 9. audit_log table — create if not exists ───────────────────────────────
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS audit_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            username VARCHAR(100),
            role VARCHAR(100),
            action VARCHAR(255) NOT NULL,
            module VARCHAR(100),
            record_id INT NULL,
            description TEXT,
            ip_address VARCHAR(45),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    $fixes[] = "✅ audit_log table — created/verified";
} catch (PDOException $e) {
    $errors[] = "❌ audit_log table — " . $e->getMessage();
}

// ─── 10. transactions table — create if not exists ───────────────────────────
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS transactions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            bill_id INT NULL,
            patient_id INT NOT NULL,
            amount DECIMAL(10,2) NOT NULL DEFAULT 0,
            payment_mode VARCHAR(50) NOT NULL DEFAULT 'Cash',
            bill_type VARCHAR(50),
            doctor_id INT NULL,
            created_by INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    $fixes[] = "✅ transactions table — created/verified";
} catch (PDOException $e) {
    $errors[] = "❌ transactions table — " . $e->getMessage();
}

// ─── 11. ipd_admissions — ensure ward_type and bed_number columns exist ──────
safe_alter($pdo,
    "ALTER TABLE ipd_admissions ADD COLUMN ward_type VARCHAR(100) NULL",
    "ipd_admissions.ward_type column"
);
safe_alter($pdo,
    "ALTER TABLE ipd_admissions ADD COLUMN bed_number VARCHAR(20) NULL",
    "ipd_admissions.bed_number column"
);
safe_alter($pdo,
    "ALTER TABLE ipd_admissions ADD COLUMN status VARCHAR(50) NOT NULL DEFAULT 'Admitted'",
    "ipd_admissions.status column"
);

?>
<!DOCTYPE html>
<html>
<head>
    <title>HMS — DB Column Fix</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 700px; margin: 40px auto; padding: 20px; }
        h2 { color: #0056b3; }
        .fix { color: #155724; background: #d4edda; padding: 6px 12px; margin: 4px 0; border-radius: 4px; }
        .err { color: #721c24; background: #f8d7da; padding: 6px 12px; margin: 4px 0; border-radius: 4px; }
        .done { background: #0056b3; color: white; padding: 15px; border-radius: 8px; margin-top: 20px; text-align: center; font-size: 1.2em; }
        a { color: white; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>
    <h2>🏥 HMS — Database Column Fix</h2>
    <p>Running all schema repairs...</p>

    <?php foreach ($fixes as $f): ?>
        <div class="fix"><?php echo $f; ?></div>
    <?php endforeach; ?>

    <?php foreach ($errors as $e): ?>
        <div class="err"><?php echo $e; ?></div>
    <?php endforeach; ?>

    <div class="done">
        ✅ Fix Complete! <?php echo count($fixes); ?> operations run, <?php echo count($errors); ?> errors.
        <br><br>
        <a href="index.php">→ Go to Dashboard</a> &nbsp;&nbsp;
        <a href="opd.php">→ Go to OPD</a>
    </div>
</body>
</html>
