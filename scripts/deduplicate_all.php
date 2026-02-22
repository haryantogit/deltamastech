<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;

$invoices = SalesInvoice::with('items')->get();
foreach ($invoices as $inv) {
    if ($inv->items->count() > 0) {
        $uniqueMap = [];
        $hasDuplicates = false;
        foreach ($inv->items as $item) {
            $key = $item->product_id . '_' . $item->qty . '_' . $item->price;
            if (!isset($uniqueMap[$key])) {
                $uniqueMap[$key] = $item->id;
            } else {
                $item->delete();
                $hasDuplicates = true;
            }
        }

        if ($hasDuplicates) {
            echo "Cleaned duplicates for: " . $inv->invoice_number . "\n";
            // Recalculate if it's not the one we manually fixed
            if ($inv->invoice_number !== 'INV/02695') {
                $newTotal = SalesInvoiceItem::where('sales_invoice_id', $inv->id)->sum('subtotal');
                $inv->update(['total_amount' => $newTotal]);
            } else {
                $inv->update(['total_amount' => 64001490]);
            }
        }
    }
}
echo "All duplicates cleared.\n";
