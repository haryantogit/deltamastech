<?php
$csvPath = 'D:\Program Receh\kledo\data-baru\pesanan-pembelian_18-Feb-2026_halaman-1.csv';
$content = file_get_contents($csvPath);
// Remove BOM
if (strpos($content, "\xEF\xBB\xBF") === 0)
    $content = substr($content, 3);
$tempFile = tmpfile();
fwrite($tempFile, $content);
fseek($tempFile, 0);

$header = fgetcsv($tempFile);
// Map 'Nomor Pesanan'
$colIndex = -1;
foreach ($header as $i => $h) {
    if (str_contains($h, 'Nomor Pesanan'))
        $colIndex = $i;
}

$orders = [];
while (($row = fgetcsv($tempFile)) !== false) {
    if ($colIndex >= 0 && isset($row[$colIndex])) {
        $orders[] = trim($row[$colIndex]);
    }
}
fclose($tempFile);

$uniqueOrders = array_unique($orders); // array_unique preserves keys?
// array_values to reindex for easier traversal
$uniqueOrders = array_values(array_unique($orders));

$found = false;
foreach ($uniqueOrders as $po) {
    if ($po == 'PO/00136') {
        $found = true;
        echo "Found PO/00136. Next orders:\n";
        continue;
    }
    if ($found) {
        echo $po . "\n";
        // Show next 5
        static $count = 0;
        if (++$count >= 5)
            break;
    }
}
