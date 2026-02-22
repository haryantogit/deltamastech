<?php

use App\Models\Stock;

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$negativeStocks = Stock::where('quantity', '<', 0)->get();

if ($negativeStocks->isEmpty()) {
    echo "No negative stock items found.\n";
} else {
    echo "Found " . $negativeStocks->count() . " items with negative stock.\n";
    foreach ($negativeStocks as $stock) {
        if ($stock->product) {
            echo "Deleting stock for Product: " . $stock->product->name . " (ID: " . $stock->product_id . ") - Qty: " . $stock->quantity . "\n";
        } else {
            echo "Deleting stock for Product ID: " . $stock->product_id . " - Qty: " . $stock->quantity . "\n";
        }
        $stock->delete();
    }
    echo "Negative stock items deleted successfully.\n";
}
