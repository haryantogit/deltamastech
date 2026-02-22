<?php
$csvPath = 'D:\Program Receh\kledo\data-baru\tagihan-pembelian_18-Feb-2026_halaman-1.csv';
$f = fopen($csvPath, 'r');
// skip bom
$param = fgets($f);
if (strpos($param, "\xEF\xBB\xBF") === 0)
    $param = substr($param, 3);
// But fgets reads a line.  
// Let's use `fgetcsv` properly after skipping BOM
$content = file_get_contents($csvPath);
if (strpos($content, "\xEF\xBB\xBF") === 0)
    $content = substr($content, 3);
$lines = explode("\n", $content);

$header = str_getcsv($lines[0]);
$header = array_map(function ($h) {
    return trim($h, " \t\n\r\0\x0B\""); }, $header);

$catatanIdx = -1;
foreach ($header as $i => $h) {
    if ($h === 'Catatan')
        $catatanIdx = $i;
}

if ($catatanIdx === -1)
    die("Catatan not found");

echo "Checking Catatan column (Index $catatanIdx):\n";
for ($i = 1; $i < 6; $i++) {
    if (isset($lines[$i])) {
        $row = str_getcsv($lines[$i]);
        if (isset($row[$catatanIdx])) {
            echo "Row $i: " . $row[$catatanIdx] . "\n";
        }
    }
}
