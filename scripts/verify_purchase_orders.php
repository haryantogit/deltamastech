<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;

$orderCount = PurchaseOrder::count();
$itemCount = PurchaseOrderItem::count();
$totalAmount = PurchaseOrder::sum('total_amount');

echo "Total Purchase Orders: $orderCount\n";
echo "Total Purchase Order Items: $itemCount\n";
echo "Total Amount Sum: " . number_format($totalAmount, 2) . "\n";

$lastOrder = PurchaseOrder::latest('id')->first();
if ($lastOrder) {
    echo "\nLast Order Details:\n";
    echo "Number: {$lastOrder->number}\n";
    echo "Date: {$lastOrder->date}\n";
    echo "Supplier: " . ($lastOrder->supplier->name ?? 'N/A') . "\n";
    echo "Total: " . number_format($lastOrder->total_amount, 2) . "\n";
    echo "Items:\n";
    foreach ($lastOrder->items as $item) {
        echo "- {$item->product->name} (Qty: {$item->quantity}, Price: " . number_format($item->unit_price, 2) . ")\n";
    }
}
