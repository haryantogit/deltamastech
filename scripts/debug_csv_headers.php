<?php

$filePath = 'd:/Program Receh/kledo/data/pengiriman_29-Jan-2026_halaman-1.csv';
$handle = fopen($filePath, 'r');
$header = fgetcsv($handle);
fclose($handle);

echo "Headers found:\n";
foreach ($header as $h) {
    echo "- '$h' (Hex: " . bin2hex($h) . ")\n";
}
