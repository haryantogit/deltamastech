<?php
require 'vendor/autoload.php';
use Carbon\Carbon;

$file = 'D:/Program Receh/kledo/data-baru/aset-tetap_17-Feb-2026_halaman-1.csv';
$handle = fopen($file, 'r');
$header = fgetcsv($handle);

$today = Carbon::create(2026, 2, 17);
$totalPrice = 0;
$totalAccumDepCalculated = 0;
$totalBookValue = 0;

while (($row = fgetcsv($handle)) !== false) {
    $price = (float) str_replace(',', '', $row[3]);
    $rate = (float) str_replace(',', '', $row[11]);
    $startDateStr = $row[14];
    $method = $row[13];
    $noDep = strtolower($row[8]) === 'ya';

    $totalPrice += $price;

    $accumDep = 0;
    if (!$noDep && !empty($startDateStr)) {
        try {
            $startDate = Carbon::createFromFormat('d/m/Y', $startDateStr);
            if ($startDate->lessThan($today)) {
                $months = $startDate->diffInMonths($today);
                if ($method === 'Straight Line') {
                    $monthlyDep = ($price * ($rate / 100)) / 12;
                    $accumDep = min($price, $monthlyDep * $months);
                }
            }
        } catch (\Exception $e) {
        }
    }

    $totalAccumDepCalculated += $accumDep;
    $totalBookValue += ($price - $accumDep);
}

echo "Total Registered Assets in CSV: 35\n";
echo "Sum of Purchase Prices: " . number_format($totalPrice, 0, ',', '.') . "\n";
echo "Calculated Sum of Accum Dep (Live): " . number_format($totalAccumDepCalculated, 0, ',', '.') . "\n";
echo "Calculated Total Book Value (Live): " . number_format($totalBookValue, 0, ',', '.') . "\n";
