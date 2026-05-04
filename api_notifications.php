<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/config/db.php';

$action = $_GET['action'] ?? '';

if ($action == 'read') {
    $id = $_GET['id'] ?? 0;
    if ($id) {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
        $stmt->execute([$id]);
    }
    echo json_encode(['success' => true]);
    exit;
}

$role = $_SESSION['role'] ?? '';
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE is_read = 0 AND (for_role = 'All' OR for_role = ? OR for_role = '') ORDER BY created_at DESC LIMIT 10");
$stmt->execute([$role]);
$notifications = $stmt->fetchAll();

echo json_encode(['success' => true, 'notifications' => $notifications, 'count' => count($notifications)]);
?>
