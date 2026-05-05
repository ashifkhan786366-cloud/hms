<?php
// HMS Diagnostic Page
header('Content-Type: text/html; charset=UTF-8');
echo "<h2>HMS Diagnostic</h2>";

// PHP Version
echo "<p><b>PHP Version:</b> " . phpversion() . "</p>";

// Extensions
echo "<p><b>PDO loaded:</b> " . (extension_loaded('pdo') ? '✅ YES' : '❌ NO') . "</p>";
echo "<p><b>pdo_mysql loaded:</b> " . (extension_loaded('pdo_mysql') ? '✅ YES' : '❌ NO') . "</p>";
echo "<p><b>mysqli loaded:</b> " . (extension_loaded('mysqli') ? '✅ YES' : '❌ NO') . "</p>";

// PHP ini file being used
echo "<p><b>PHP ini file:</b> " . php_ini_loaded_file() . "</p>";

// Test DB connection
echo "<hr><h3>Database Connection Test</h3>";
try {
    $pdo = new PDO("mysql:host=localhost;dbname=hms_db", "root", "123321000");
    echo "<p style='color:green'><b>✅ DB Connection: SUCCESS</b></p>";
    
    // Test users table
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM users");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p><b>Users in DB:</b> " . $row['cnt'] . "</p>";
    
} catch (Exception $e) {
    echo "<p style='color:red'><b>❌ DB Error:</b> " . $e->getMessage() . "</p>";
}

echo "<hr><p><a href='login.php'>Go to Login Page</a></p>";
?>
