<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/config/db.php';

$labels = [];
$data = [];

for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $labels[] = date('d M', strtotime($date));
    
    $stmt = $pdo->prepare("SELECT SUM(amount) FROM transactions WHERE DATE(created_at) = ?");
    $stmt->execute([$date]);
    $sum = $stmt->fetchColumn() ?: 0;
    
    $data[] = $sum;
}

echo json_encode(['labels' => $labels, 'data' => $data]);
?>
