<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\SalesInvoice;

$inv = SalesInvoice::where('invoice_number', 'INV/02695')->with('items.product')->first();
if ($inv) {
    echo "Total: " . $inv->total_amount . "\n";
    foreach ($inv->items as $item) {
        echo "Item SKU: " . ($item->product->sku ?? 'N/A') . " | Qty: " . $item->qty . " | Price: " . $item->price . " | Subtotal: " . $item->subtotal . "\n";
    }
} else {
    echo "Invoice not found\n";
}
