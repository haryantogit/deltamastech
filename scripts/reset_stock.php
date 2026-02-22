<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

// Fix KARUNG specifically first
$p = Product::find(1350);
if ($p) {
    echo "Resetting KARUNG (Current: {$p->stock})...\n";

    // Delete orphan movements
    // Be careful not to delete sales if they existed (but check_sales says 0)
    $deleted = StockMovement::where('product_id', 1350)->delete();
    echo "Deleted {$deleted} stock movements.\n";

    $p->stock = 0;
    $p->save();

    // Also reset per-warehouse stock
    Stock::where('product_id', 1350)->update(['quantity' => 0]);

    echo "KARUNG stock reset to 0.\n";
}

// General check for others?
// For now, just fix KARUNG as requested/verified.
