<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Stock;
use App\Models\Product;

echo "Analyzing Negative Stocks...\n";

// Check Per-Warehouse Stock
$negativeStocks = Stock::where('quantity', '<', 0)->get();
echo "Found {$negativeStocks->count()} negative warehouse stock records.\n";

$totalNegativeQty = $negativeStocks->sum('quantity');
echo "Total Negative Quantity: {$totalNegativeQty}\n";

// Check Global Product Stock
$negativeProducts = Product::where('stock', '<', 0)->get();
echo "Found {$negativeProducts->count()} products with negative global stock.\n";
