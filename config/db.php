<?php
date_default_timezone_set('Asia/Kolkata');

// =============================================
//  Database Configuration — Environment Safe
//  Credentials load hote hain ENV variables se
// =============================================

// Load .env file if exists (for local development)
$env_file = __DIR__ . '/../.env';
if (file_exists($env_file)) {
    $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            if (!array_key_exists($key, $_SERVER) && !array_key_exists($key, $_ENV)) {
                putenv("$key=$value");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
    }
}

// Database credentials from environment variables
define('DB_HOST', getenv('DB_HOST') ?: (getenv('MYSQLHOST') ?: '127.0.0.1'));
define('DB_USER', getenv('DB_USER') ?: (getenv('MYSQLUSER') ?: 'root'));
define('DB_PASS', getenv('DB_PASS') ?: (getenv('MYSQLPASSWORD') ?: ''));
define('DB_NAME', getenv('DB_NAME') ?: (getenv('MYSQLDATABASE') ?: 'hms_db'));
define('DB_PORT', getenv('DB_PORT') ?: (getenv('MYSQLPORT') ?: '3306'));

try {
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Production mein detailed error mat dikhao
    $is_debug = (getenv('APP_DEBUG') === 'true');
    if ($is_debug) {
        die("ERROR: Could not connect to database. " . $e->getMessage());
    } else {
        error_log("HMS DB Connection Error: " . $e->getMessage());
        die("Database connection failed. Please contact administrator.");
    }
}

// Error Reporting — Production mein OFF
if (getenv('APP_ENV') === 'production') {
    error_reporting(0);
    ini_set('display_errors', 0);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// App Configuration (Dynamic from Database)
try {
    $set_stmt = $pdo->query("SELECT setting_key, setting_value FROM hospital_settings");
    while ($row = $set_stmt->fetch()) {
        if (!defined($row['setting_key'])) {
            define($row['setting_key'], $row['setting_value']);
        }
    }
} catch (Exception $e) {
    // Failsafe defaults if table doesn't exist yet
    if (!defined('APP_NAME'))
        define('APP_NAME', 'SANKHLA HOSPITAL');
    if (!defined('APP_LOGO'))
        define('APP_LOGO', 'assets/logo.png');
    if (!defined('PRIMARY_COLOR'))
        define('PRIMARY_COLOR', '#0d6efd');
    if (!defined('SECONDARY_COLOR'))
        define('SECONDARY_COLOR', '#212529');
    if (!defined('HEADER_FONT'))
        define('HEADER_FONT', 'Arial, sans-serif');
    if (!defined('CURRENCY'))
        define('CURRENCY', '₹');
}

// Start Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>