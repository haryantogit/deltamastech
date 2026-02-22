<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\SalesInvoice;

$file = 'd:/Program Receh/kledo/data/tagihan_29-Jan-2026_halaman-1.csv';
if (!file_exists($file)) {
    die("File not found\n");
}

$handle = fopen($file, "r");
$header = fgetcsv($handle);

$totals = [];
while (($row = fgetcsv($handle)) !== false) {
    if (count($row) < 56)
        continue;
    $invNumber = trim($row[10]); // *Nomor Tagihan
    $grandTotal = (float) str_replace(',', '', $row[55]); // Jumlah Total Tagihan

    if ($invNumber) {
        $totals[$invNumber] = $grandTotal;
    }
}
fclose($handle);

echo "Found " . count($totals) . " unique invoices in CSV.\n";

$updatedCount = 0;
foreach ($totals as $number => $total) {
    $inv = SalesInvoice::where('invoice_number', $number)->first();
    if ($inv) {
        $inv->update(['total_amount' => $total]);
        $updatedCount++;
    }
}

echo "Updated " . $updatedCount . " invoices in database.\n";

// Special check for INV/02695
$check = SalesInvoice::where('invoice_number', 'INV/02695')->first();
if ($check) {
    echo "Double check INV/02695: Total = " . $check->total_amount . " | Warehouse ID = " . $check->warehouse_id . "\n";
}
