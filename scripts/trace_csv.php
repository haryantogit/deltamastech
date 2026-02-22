<?php
$csvPath = 'D:\Program Receh\kledo\data-baru\pesanan-pembelian_18-Feb-2026_halaman-1.csv';
$content = file_get_contents($csvPath);
// Remove BOM
if (strpos($content, "\xEF\xBB\xBF") === 0) {
    $content = substr($content, 3);
}
$tempFile = tmpfile();
fwrite($tempFile, $content);
fseek($tempFile, 0);

$header = fgetcsv($tempFile);
$header = array_map(function ($h) {
    return trim($h, " \t\n\r\0\x0B\""); }, $header);
$data = fgetcsv($tempFile);
$row = array_combine($header, $data);

foreach ($row as $key => $val) {
    echo "[$key] => \"$val\" (len: " . strlen($val) . ")\n";
    echo "Key len: " . strlen($key) . " Hex: " . bin2hex($key) . "\n";
}
fclose($tempFile);
