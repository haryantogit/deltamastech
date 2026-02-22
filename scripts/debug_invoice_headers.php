<?php

$filePath = 'd:/Program Receh/kledo/data/tagihan_29-Jan-2026_halaman-1.csv';
$handle = fopen($filePath, 'r');
$header = fgetcsv($handle);
fclose($handle);

echo "Headers found for Sales Invoice:\n";
foreach ($header as $h) {
    echo "- '$h'\n";
}
