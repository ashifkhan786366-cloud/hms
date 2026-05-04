<?php
require_once __DIR__ . '/config/db.php';

echo "<h1>HMS Database Importer</h1>";

if (isset($_GET['run']) && $_GET['run'] == 'yes') {
    $sql_file = __DIR__ . '/hms_schema.sql';
    
    if (file_exists($sql_file)) {
        $sql = file_get_contents($sql_file);
        
        // Remove comments
        $sql = preg_replace('/--.*$/m', '', $sql);
        $sql = preg_replace('/^\s*$/m', '', $sql);
        
        // Split by semicolon
        $statements = explode(';', $sql);
        
        $successCount = 0;
        $errorCount = 0;
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement)) {
                try {
                    $pdo->exec($statement);
                    $successCount++;
                } catch (PDOException $e) {
                    echo "<p style='color:red;'>Error in statement: " . htmlspecialchars($statement) . "<br>Error: " . $e->getMessage() . "</p>";
                    $errorCount++;
                }
            }
        }
        
        if ($errorCount == 0) {
            echo "<h3 style='color:green;'>✅ Database Schema Imported Successfully! ($successCount queries executed)</h3>";
            echo "<p>You can now login with username <b>admin</b> and password <b>password</b></p>";
            echo "<p><a href='login.php'>Go to Login</a></p>";
        } else {
            echo "<h3 style='color:orange;'>⚠️ Import finished with $errorCount errors. ($successCount queries succeeded)</h3>";
        }
        
    } else {
        echo "<h3 style='color:red;'>❌ hms_schema.sql file not found!</h3>";
    }
} else {
    echo "<p>Click the button below to setup the database tables. (Only do this once!)</p>";
    echo "<form method='GET'><input type='hidden' name='run' value='yes'><button type='submit' style='padding: 10px 20px; font-size: 16px; background: #0d6efd; color: white; border: none; border-radius: 5px; cursor: pointer;'>Import Database Now</button></form>";
}
?>
