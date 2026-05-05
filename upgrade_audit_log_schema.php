<?php
require_once 'config/db.php';

try {
    // Check existing columns
    $stmt = $pdo->query("DESCRIBE audit_log");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $queries = [];
    
    if (!in_array('username', $columns)) {
        $queries[] = "ALTER TABLE audit_log ADD COLUMN username VARCHAR(100) AFTER user_id";
    }
    if (!in_array('role', $columns)) {
        $queries[] = "ALTER TABLE audit_log ADD COLUMN role VARCHAR(50) AFTER username";
    }
    if (!in_array('description', $columns)) {
        $queries[] = "ALTER TABLE audit_log ADD COLUMN description TEXT AFTER record_id";
    }
    
    foreach ($queries as $q) {
        echo "Executing: $q\n";
        $pdo->exec($q);
    }
    
    // Attempt to update existing rows with username and role from users table
    if (!empty($queries)) {
        $updateSql = "UPDATE audit_log al JOIN users u ON al.user_id = u.id SET al.username = u.username, al.role = u.role WHERE al.username IS NULL";
        $pdo->exec($updateSql);
        echo "Updated old records.\n";
    }
    
    echo "Database schema updated successfully.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
