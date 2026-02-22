<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Support\Facades\DB;

echo "Starting deletion of Purchase Orders...\n";

$orders = PurchaseOrder::all();
$count = $orders->count();

if ($count === 0) {
    echo "No purchase orders found.\n";
    exit;
}

echo "Found {$count} purchase orders. Deleting...\n";

DB::beginTransaction();

try {
    foreach ($orders as $order) {
        // Delete items explicitly first, though database cascading might handle it
        // It's safer to be explicit in scripts
        $order->items()->delete();

        // Delete the order
        $order->delete();
    }

    DB::commit();
    echo "Successfully deleted {$count} purchase orders and their items.\n";
} catch (\Exception $e) {
    DB::rollBack();
    echo "Error deleting purchase orders: " . $e->getMessage() . "\n";
}
