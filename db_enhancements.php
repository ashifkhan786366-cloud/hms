<?php
require_once __DIR__ . '/config/db.php';

$sql = "
CREATE TABLE IF NOT EXISTS transactions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  bill_id VARCHAR(50), patient_id INT, amount DECIMAL(10,2),
  payment_mode ENUM('Cash','UPI','Card','Other'),
  bill_type ENUM('OPD','Lab','IPD','Pharmacy'),
  created_at DATETIME DEFAULT NOW(), doctor_id INT, created_by INT
);

CREATE TABLE IF NOT EXISTS lab_orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  bill_id VARCHAR(50), patient_id INT, test_name VARCHAR(200),
  status ENUM('Pending','Completed') DEFAULT 'Pending',
  result_value TEXT, done_at DATETIME, created_at DATETIME DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS beds (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ward_name VARCHAR(100), bed_number VARCHAR(20),
  status ENUM('Available','Occupied') DEFAULT 'Available',
  patient_id INT DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS follow_ups (
  id INT AUTO_INCREMENT PRIMARY KEY,
  patient_id INT, visit_id INT, follow_date DATE,
  notes TEXT, created_at DATETIME DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS prescriptions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  patient_id INT, visit_id INT, medicine_name VARCHAR(200),
  dose VARCHAR(100), duration VARCHAR(100), instructions TEXT,
  created_at DATETIME DEFAULT NOW(), doctor_id INT
);

CREATE TABLE IF NOT EXISTS notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  type VARCHAR(100), message TEXT, is_read TINYINT DEFAULT 0,
  created_at DATETIME DEFAULT NOW(), for_role VARCHAR(50)
);

CREATE TABLE IF NOT EXISTS audit_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT, action VARCHAR(100), module VARCHAR(100),
  record_id INT, ip_address VARCHAR(50), created_at DATETIME DEFAULT NOW()
);
";

try {
    $pdo->exec($sql);
    echo "Tables created successfully.\n";
} catch (PDOException $e) {
    echo "Error creating tables: " . $e->getMessage() . "\n";
}
?>
