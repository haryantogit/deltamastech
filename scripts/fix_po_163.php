<?php

use App\Models\PurchaseOrder;

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$po = PurchaseOrder::where('number', 'PO/00163')->first();

if ($po) {
    echo "Current Status: " . $po->status . "\n";
    $po->update(['status' => 'ordered']);
    echo "New Status: " . $po->status . "\n";
    echo "PO/00163 has been reverted to 'ordered' (Dipesan/Disetujui).\n";
} else {
    echo "PO/00163 not found.\n";
}
