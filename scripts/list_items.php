<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\SalesInvoice;

$inv = SalesInvoice::where('invoice_number', 'INV/02695')->with('items.product')->first();
if ($inv) {
    echo "ITEMS for " . $inv->invoice_number . ":\n";
    foreach ($inv->items as $item) {
        echo "ID: " . $item->id . " | Product: " . ($item->product->name ?? 'N/A') . " | Qty: " . $item->qty . " | Created At: " . $item->created_at . "\n";
    }
} else {
    echo "Invoice not found\n";
}
