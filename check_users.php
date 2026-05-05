<?php
try {
    $pdo = new PDO("mysql:host=localhost;dbname=hms_db", "root", "123321000");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get columns first
    $cols = $pdo->query("DESCRIBE users")->fetchAll(PDO::FETCH_ASSOC);
    echo "<h3>Users Table Columns:</h3><ul>";
    foreach($cols as $c) echo "<li>{$c['Field']} ({$c['Type']})</li>";
    echo "</ul>";
    
    // Get users
    $stmt = $pdo->query("SELECT * FROM users LIMIT 10");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<h3>Users:</h3><pre>" . print_r($users, true) . "</pre>";
    
} catch(Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
