<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/config/db.php';

$query = $_GET['q'] ?? '';

if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare("SELECT id, mr_number, full_name, phone FROM patients WHERE full_name LIKE ? OR mr_number LIKE ? OR phone LIKE ? LIMIT 10");
$search = "%{$query}%";
$stmt->execute([$search, $search, $search]);
$results = $stmt->fetchAll();

echo json_encode($results);
?>
