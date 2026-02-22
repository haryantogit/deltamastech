<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\SalesInvoice;

$inv = SalesInvoice::where('invoice_number', 'INV/02695')->with('items.product')->first();
if ($inv) {
    echo "INVOICE: " . $inv->invoice_number . " (ID: " . $inv->id . ")\n";
    echo "Total Amount in DB: " . $inv->total_amount . "\n";
    echo "Item Count: " . $inv->items->count() . "\n";
    $calculatedTotal = 0;
    foreach ($inv->items as $index => $item) {
        $subtotal = $item->qty * $item->price;
        echo ($index + 1) . ". SKU: " . ($item->product->sku ?? 'N/A') . " | Qty: " . $item->qty . " | Price: " . $item->price . " | Subtotal in DB: " . $item->subtotal . " | Calculated: " . $subtotal . "\n";
        $calculatedTotal += $item->subtotal;
    }
    echo "Sum of Subtotals: " . $calculatedTotal . "\n";
} else {
    echo "Invoice not found\n";
}
