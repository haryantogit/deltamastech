<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;

$totalProducts = Product::count();
$regularProducts = Product::where('is_fixed_asset', false)->count();
$fixedAssets = Product::where('is_fixed_asset', true)->count();
$registeredFixedAssets = Product::where('is_fixed_asset', true)->where('status', 'registered')->count();
$draftFixedAssets = Product::where('is_fixed_asset', true)->where('status', 'draft')->count();
$disposedFixedAssets = Product::where('is_fixed_asset', true)->where('status', 'disposed')->count();

echo "Total Products in DB: $totalProducts\n";
echo "Regular Products: $regularProducts\n";
echo "Total Fixed Assets: $fixedAssets\n";
echo "Registered Fixed Assets: $registeredFixedAssets\n";
echo "Draft Fixed Assets: $draftFixedAssets\n";
echo "Disposed Fixed Assets: $disposedFixedAssets\n";

$visibleQueryCount = Product::where(
    fn($q) =>
    $q->where('is_fixed_asset', false)
        ->orWhere(fn($sq) => $sq->where('is_fixed_asset', true)->where('status', 'registered'))
)->count();

echo "Visible Products (Filtered): $visibleQueryCount\n";
echo "Expected Visible: " . ($regularProducts + $registeredFixedAssets) . "\n";

if ($visibleQueryCount == ($regularProducts + $registeredFixedAssets)) {
    echo "SUCCESS: Filtering logic is correct!\n";
} else {
    echo "FAILURE: Filtering logic mismatch!\n";
}
