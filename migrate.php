<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(300);

require_once 'config/db.php';

echo "<style>
body{font-family:monospace;padding:20px;background:#1a1a2e;color:#eee}
.ok{color:#00ff88} .err{color:#ff4444} 
h2{color:#00d4ff}
</style>";
echo "<h2>🏥 HMS Database Migration</h2><pre>";

$sql_file = __DIR__ . '/database/hms_complete.sql';

if(!file_exists($sql_file)){
    die("❌ SQL file not found! Path: " . $sql_file);
}

$sql = file_get_contents($sql_file);
$statements = array_filter(
    array_map('trim', explode(';', $sql)),
    fn($s) => strlen(trim($s)) > 5
);

$ok = 0; $fail = 0;

foreach($statements as $stmt){
    try {
        $pdo->exec($stmt);
        $ok++;
        echo "<span class='ok'>✅ OK</span>\n";
    } catch(PDOException $e){
        $fail++;
        echo "<span class='err'>❌ " . $e->getMessage() . "</span>\n";
    }
}

echo "\n<b>============================</b>\n";
echo "<span class='ok'>✅ Success: {$ok}</span>\n";
echo "<span class='err'>❌ Failed:  {$fail}</span>\n";
echo "<b>============================</b>\n";
echo "\n🚀 <b>Done! Delete migrate.php after this.</b>";
echo "</pre>";
?>
