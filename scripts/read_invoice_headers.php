<?php
$csvPath = 'D:\Program Receh\kledo\data-baru\tagihan-pembelian_18-Feb-2026_halaman-1.csv';
if (!file_exists($csvPath))
    die("File not found");
$f = fopen($csvPath, 'r');
// skip bom
$param = fgets($f);
if (strpos($param, "\xEF\xBB\xBF") === 0)
    $param = substr($param, 3);
// But fgets reads a line. 
// Let's use file_get_contents for safety on BOM, then parse str
$content = file_get_contents($csvPath);
if (strpos($content, "\xEF\xBB\xBF") === 0)
    $content = substr($content, 3);
$lines = explode("\n", $content);
$header = str_getcsv($lines[0]);
$header = array_map(function ($h) {
    return trim($h, " \t\n\r\0\x0B\""); }, $header);
echo json_encode($header);
