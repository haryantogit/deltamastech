<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PurchaseInvoice;
use App\Models\PurchaseDelivery;

echo "--- A. 5 Tagihan Pembelian Terakhir (Purchase Invoices) ---\n";
$invoices = PurchaseInvoice::latest()->take(5)->get();
foreach ($invoices as $inv) {
    echo "ID: {$inv->id} | No: {$inv->number} | Tgl: {$inv->date} | Total: Rp " . number_format($inv->total_amount, 0) . " | Status: {$inv->status}\n";
}

echo "\n--- B. 5 Pengiriman Pembelian Terakhir (Purchase Deliveries) ---\n";
$deliveries = PurchaseDelivery::latest()->take(5)->get();
foreach ($deliveries as $del) {
    echo "ID: {$del->id} | No: {$del->number} | Tgl: {$del->created_at->format('Y-m-d H:i')} | Valid: {$del->is_valid} | Status: {$del->status}\n";
}
