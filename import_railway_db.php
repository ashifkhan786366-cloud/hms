<?php
require_once __DIR__ . '/config/db.php';

echo "<h1>HMS Database Importer</h1>";

// Security check (basic)
if (isset($_GET['run']) && $_GET['run'] == 'yes') {
    $sql_file = __DIR__ . '/hms_schema.sql';
    
    if (file_exists($sql_file)) {
        $sql = file_get_contents($sql_file);
        
        try {
            // execute the entire schema
            $pdo->exec($sql);
            echo "<h3 style='color:green;'>✅ Database Schema Imported Successfully!</h3>";
            echo "<p>You can now login with username <b>admin</b> and password <b>password</b></p>";
            echo "<p><a href='index.php'>Go to Login</a></p>";
            
            // Delete this file after successful run for security
            @unlink(__FILE__);
        } catch (PDOException $e) {
            echo "<h3 style='color:red;'>❌ Error executing SQL</h3>";
            echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
        }
    } else {
        echo "<h3 style='color:red;'>❌ hms_schema.sql file not found!</h3>";
    }
} else {
    echo "<p>Click the button below to setup the database tables. (Only do this once!)</p>";
    echo "<form method='GET'><input type='hidden' name='run' value='yes'><button type='submit' style='padding: 10px 20px; font-size: 16px; background: #0d6efd; color: white; border: none; border-radius: 5px; cursor: pointer;'>Import Database Now</button></form>";
}
?>
