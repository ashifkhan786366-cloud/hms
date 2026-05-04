<?php
require_once __DIR__ . '/config/db.php';

echo "<h1>HMS Database Importer (v3)</h1>";

if (isset($_GET['run']) && $_GET['run'] == 'yes') {
    $sql_file = __DIR__ . '/hms_schema.sql';
    
    if (file_exists($sql_file)) {
        $sql = file_get_contents($sql_file);
        
        // Split SQL statements intelligently
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        
        $successCount = 0;
        $errorCount = 0;
        
        echo "<table border='1' cellpadding='5' style='border-collapse:collapse; width:100%;'>";
        echo "<tr><th>Status</th><th>Query Snippet</th><th>Error Message</th></tr>";
        
        // Turn off foreign key checks temporarily
        $pdo->exec("SET FOREIGN_KEY_CHECKS=0;");
        
        foreach ($statements as $statement) {
            if (empty($statement) || strpos($statement, '/*') === 0 || strpos($statement, '--') === 0) {
                continue;
            }
            
            try {
                $pdo->exec($statement);
                $successCount++;
                echo "<tr><td style='color:green'>✅ Success</td><td>" . htmlspecialchars(substr($statement, 0, 50)) . "...</td><td>-</td></tr>";
            } catch (PDOException $e) {
                $errorCount++;
                echo "<tr><td style='color:red'>❌ Error</td><td>" . htmlspecialchars(substr($statement, 0, 50)) . "...</td><td style='color:red'>" . $e->getMessage() . "</td></tr>";
            }
        }
        
        // Turn foreign key checks back on
        $pdo->exec("SET FOREIGN_KEY_CHECKS=1;");
        
        echo "</table>";
        
        echo "<h3>Summary: $successCount succeeded, $errorCount failed.</h3>";
        
        if ($errorCount == 0 || $successCount > 10) {
            echo "<h2 style='color:green;'>Looks like the tables are created!</h2>";
            echo "<p><a href='login.php' style='padding: 10px 20px; font-size: 16px; background: #0d6efd; color: white; text-decoration: none; border-radius: 5px;'>Go to Login</a></p>";
        }
        
    } else {
        echo "<h3 style='color:red;'>❌ hms_schema.sql file not found!</h3>";
    }
} else {
    echo "<p>Click the button below to setup the database tables.</p>";
    echo "<form method='GET'><input type='hidden' name='run' value='yes'><button type='submit' style='padding: 10px 20px; font-size: 16px; background: #0d6efd; color: white; border: none; border-radius: 5px; cursor: pointer;'>Import Database Now</button></form>";
}

// Check existing tables
echo "<h3>Current Tables in Database:</h3>";
try {
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    if (count($tables) > 0) {
        echo "<ul>";
        foreach ($tables as $table) {
            echo "<li>$table</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>No tables found.</p>";
    }
} catch (Exception $e) {
    echo "Could not fetch tables: " . $e->getMessage();
}
?>
