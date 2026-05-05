<?php
header('Content-Type: application/json');
$env_keys = array_keys($_ENV);
$server_keys = array_keys($_SERVER);
$getenv_host = getenv('MYSQLHOST');
$getenv_db = getenv('MYSQLDATABASE');

echo json_encode([
    'message' => 'Environment Test',
    'getenv_MYSQLHOST' => $getenv_host,
    'getenv_MYSQLDATABASE' => $getenv_db,
    'env_keys' => $env_keys,
    'server_keys' => $server_keys,
    'has_MYSQLHOST_in_server' => isset($_SERVER['MYSQLHOST']),
    'has_MYSQLHOST_in_env' => isset($_ENV['MYSQLHOST'])
]);
?>
