<?php
// Auto-detect Railway.app OR local XAMPP/WAMP
$db_host = getenv('MYSQLHOST') 
        ?: getenv('DB_HOST') 
        ?: 'localhost';

$db_port = getenv('MYSQLPORT') 
        ?: getenv('DB_PORT') 
        ?: '3306';

$db_name = getenv('MYSQLDATABASE') 
        ?: getenv('DB_NAME') 
        ?: 'hms_db';

$db_user = getenv('MYSQLUSER') 
        ?: getenv('DB_USER') 
        ?: 'root';

$db_pass = getenv('MYSQLPASSWORD') 
        ?: getenv('DB_PASS') 
        ?: '';

try {
    $pdo = new PDO(
        "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4",
        $db_user,
        $db_pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
    $conn = new mysqli(
        $db_host, $db_user, $db_pass, 
        $db_name, (int)$db_port
    );
    if ($conn->connect_error) {
        throw new Exception("Connection failed");
    }
    $conn->set_charset("utf8mb4");
} catch(Exception $e) {
    error_log("DB Error: " . $e->getMessage());
    http_response_code(500);
    die(json_encode([
        'error' => 'Database connection failed',
        'details' => $e->getMessage(),
        'host' => $db_host,
        'user' => $db_user,
        'db' => $db_name
    ]));
}
?>