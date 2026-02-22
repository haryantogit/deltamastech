<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\PurchaseDelivery;
use App\Models\StockMovement;
use App\Services\StockService;
use Illuminate\Support\Facades\DB;

echo "Starting Stock Movement Repair...\n";

// Fetch all "Received" Purchase Deliveries
$deliveries = PurchaseDelivery::where('status', 'received')->with(['items', 'purchaseOrder'])->get();

$fixedCount = 0;
$skippedCount = 0;

foreach ($deliveries as $delivery) {
    // Check if ANY stock movement exists for this delivery
    $exists = StockMovement::where('reference_type', PurchaseDelivery::class)
        ->where('reference_id', $delivery->id)
        ->exists();

    if ($exists) {
        $skippedCount++;
        continue;
    }

    echo "Fixing Delivery: {$delivery->number} (ID: {$delivery->id})...\n";

    DB::transaction(function () use ($delivery) {
        foreach ($delivery->items as $item) {
            if ($item->product && $item->product->track_inventory) {
                StockService::updateStock(
                    productId: $item->product_id,
                    warehouseId: $delivery->purchaseOrder->warehouse_id ?? 1,
                    quantity: $item->quantity,
                    type: 'purchase',
                    referenceType: PurchaseDelivery::class,
                    referenceId: $delivery->id,
                    description: "Penerimaan Barang #{$delivery->number} (Repair)"
                );
                echo " - Added stock for item: {$item->product->name} (+{$item->quantity})\n";
            }
        }
    });

    $fixedCount++;
}

echo "\nRepair Complete.\n";
echo "Fixed: $fixedCount deliveries.\n";
echo "Skipped: $skippedCount deliveries (already had stock movements).\n";
