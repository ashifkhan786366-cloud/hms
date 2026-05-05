<?php
set_time_limit(0);
$url = 'https://github.com/tecnickcom/tcpdf/archive/refs/heads/main.zip';
$zipFile = __DIR__ . '/tcpdf.zip';
$extractPath = __DIR__ . '/libs/';

if (!file_exists(__DIR__ . '/libs')) {
    mkdir(__DIR__ . '/libs', 0777, true);
}

echo "Downloading TCPDF...\n";
$fp = fopen($zipFile, 'w+');
$ch = curl_init(str_replace(" ","%20",$url));
curl_setopt($ch, CURLOPT_TIMEOUT, 300);
curl_setopt($ch, CURLOPT_FILE, $fp);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_exec($ch);
curl_close($ch);
fclose($fp);

echo "Extracting TCPDF...\n";
$zip = new ZipArchive;
$res = $zip->open($zipFile);
if ($res === TRUE) {
    $zip->extractTo($extractPath);
    $zip->close();
    rename($extractPath . 'tcpdf-main', $extractPath . 'tcpdf');
    echo "Extracted successfully to libs/tcpdf/\n";
    unlink($zipFile);
} else {
    echo "Failed to extract zip file\n";
}
