<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\PurchaseInvoice;
use App\Models\PurchaseOrder;
use App\Models\StockMovement;
use App\Models\PurchaseDelivery;

// Inspect PI/00227
echo "Inspecting PI/00227...\n";
$pi = PurchaseInvoice::with(['purchaseOrder', 'items'])->where('number', 'PI/00227')->first();

if (!$pi) {
    echo "PI/00227 not found.\n";
    exit;
}

echo "Invoice ID: {$pi->id}\n";
echo "PO ID: " . ($pi->purchase_order_id ?? 'None') . "\n";

if ($pi->purchaseOrder) {
    echo "PO Number: {$pi->purchaseOrder->number}\n";
    $deliveries = $pi->purchaseOrder->deliveries()->get();
    echo "Deliveries Count: {$deliveries->count()}\n";
    foreach ($deliveries as $d) {
        echo " - Delivery: {$d->number} (Status: {$d->status})\n";
    }
} else {
    echo "No PO linked.\n";
}

// Check Stock Movements for items in this PI
echo "\nChecking Stock Movements for Items:\n";
foreach ($pi->items as $item) {
    $productName = $item->product ? $item->product->name : 'Unknown Product';
    echo "Item: {$item->description} (Product: $productName, Qty: {$item->quantity})\n";

    $movements = StockMovement::where('reference_type', PurchaseInvoice::class)
        ->where('reference_id', $pi->id)
        ->where('product_id', $item->product_id)
        ->get();

    echo " - Stock Movements from Invoice: {$movements->count()}\n";

    if ($pi->purchaseOrder) {
        // Check movements from PO/Delivery
        $deliveryIds = $pi->purchaseOrder->deliveries->pluck('id');
        if ($deliveryIds->isNotEmpty()) {
            $poMovements = StockMovement::where('reference_type', PurchaseDelivery::class)
                ->whereIn('reference_id', $deliveryIds)
                ->where('product_id', $item->product_id)
                ->get();
            echo " - Stock Movements from Linked Deliveries: {$poMovements->count()}\n";
        } else {
            echo " - No Linked Deliveries found.\n";
        }
    }
}
