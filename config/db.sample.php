<?php
// config/db.php ka sample/template
// Is file ko copy karke db.php banao aur credentials fill karo

date_default_timezone_set('Asia/Kolkata');

define('DB_HOST', 'localhost');        // Ya Railway MYSQLHOST
define('DB_USER', 'root');             // Ya Railway MYSQLUSER
define('DB_PASS', 'your_password');    // Ya Railway MYSQLPASSWORD
define('DB_NAME', 'hms_db');           // Ya Railway MYSQLDATABASE
define('DB_PORT', '3306');             // Ya Railway MYSQLPORT

try {
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("ERROR: " . $e->getMessage());
}

session_start();
?>
