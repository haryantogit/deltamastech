<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

$productId = 1350; // KARUNG

echo "Stock Movements for KARUNG:\n";
$movements = StockMovement::where('product_id', $productId)
    ->select('type', DB::raw('count(*) as count'), DB::raw('sum(quantity) as total_qty'))
    ->groupBy('type')
    ->get();

foreach ($movements as $m) {
    echo "Type: {$m->type} | Count: {$m->count} | Total Qty: {$m->total_qty}\n";
}

$balance = StockMovement::where('product_id', $productId)->sum('quantity');
echo "\nCalculated Balance from Movements: {$balance}\n";

$realStock = \App\Models\Product::find($productId)->stock;
echo "Real Product Stock: {$realStock}\n";
