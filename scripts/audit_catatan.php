<?php
$files = [
    'd:/Program Receh/kledo/data/tagihan_29-Jan-2026_halaman-1.csv',
    'd:/Program Receh/kledo/data/tagihan_29-Jan-2026_halaman-2.csv',
    'd:/Program Receh/kledo/data/tagihan_29-Jan-2026_halaman-3.csv',
];

$total = 0;
foreach ($files as $file) {
    if (!file_exists($file))
        continue;
    $f = fopen($file, 'r');
    $h = fgetcsv($f);
    $m = array_flip($h);
    $c = 0;
    while (($row = fgetcsv($f)) !== FALSE) {
        if (!empty($row[$m['Catatan']]) || !empty($row[$m['Pesan']])) {
            $c++;
        }
    }
    fclose($f);
    echo "$file: $c records with Catatan/Pesan\n";
    $total += $c;
}
echo "Grand Total: $total\n";
