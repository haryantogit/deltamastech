<?php

use App\Models\PurchaseInvoice;
use App\Models\PurchaseOrder;
use App\Models\StockMovement;

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
    echo "Item: {$item->description} (Qty: {$item->quantity})\n";
    $movements = StockMovement::where('reference_type', PurchaseInvoice::class)
        ->where('reference_id', $pi->id)
        ->where('product_id', $item->product_id)
        ->get();

    echo " - Stock Movements from Invoice: {$movements->count()}\n";

    if ($pi->purchase_order_id) {
        // Check movements from PO/Delivery
        $poMovements = StockMovement::where('reference_type', \App\Models\PurchaseDelivery::class)
            ->whereIn('reference_id', $pi->purchaseOrder->deliveries->pluck('id'))
            ->where('product_id', $item->product_id)
            ->get();
        echo " - Stock Movements from Linked Deliveries: {$poMovements->count()}\n";
    }
}
