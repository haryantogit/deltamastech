<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PurchaseInvoice;
use App\Models\PurchaseDelivery;

echo "--- 5 Tagihan Terakhir (By ID) ---\n";
$invoices = PurchaseInvoice::orderBy('id', 'desc')->take(5)->get();
foreach ($invoices as $inv) {
    echo "ID: {$inv->id} | No: {$inv->number} | Status: {$inv->status}\n";
}

echo "\n--- 5 Pengiriman Terakhir (By ID) ---\n";
$deliveries = PurchaseDelivery::orderBy('id', 'desc')->take(5)->get();
foreach ($deliveries as $del) {
    echo "ID: {$del->id} | No: {$del->number} | Status: {$del->status}\n";
}

echo "\n--- Cek Transaksi PI/00227 ---\n";
$target = PurchaseInvoice::where('number', 'PI/00227')->first();
if ($target) {
    echo "Ditemukan: ID {$target->id} | No: {$target->number} | Status: {$target->status}\n";
} else {
    echo "PI/00227 Tidak Ditemukan.\n";
}
