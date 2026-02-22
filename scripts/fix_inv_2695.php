<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;

$inv = SalesInvoice::where('invoice_number', 'INV/02695')->first();
if ($inv) {
    echo "INVOICE FOUND: " . $inv->invoice_number . "\n";
    $items = SalesInvoiceItem::where('sales_invoice_id', $inv->id)->get();
    echo "TOTAL ITEMS: " . $items->count() . "\n";

    // Group items by unique properties to identify duplicates
    $uniqueMap = [];
    foreach ($items as $item) {
        $key = $item->product_id . '_' . $item->qty . '_' . $item->price;
        if (!isset($uniqueMap[$key])) {
            $uniqueMap[$key] = $item->id;
            echo "Keeping Item ID: " . $item->id . " | Product: " . ($item->product->name ?? 'N/A') . " | Qty: " . $item->qty . " | Price: " . $item->price . "\n";
        } else {
            echo "Deleting Duplicate Item ID: " . $item->id . "\n";
            $item->delete();
        }
    }

    // Re-verify sum
    $items = SalesInvoiceItem::where('sales_invoice_id', $inv->id)->get();
    $sum = $items->sum('subtotal');
    echo "New Items Sum: " . $sum . "\n";

    // Set to requested total
    $inv->update(['total_amount' => 64001490]);
    echo "Invoice Updated to: " . $inv->total_amount . "\n";
} else {
    echo "Invoice not found\n";
}
