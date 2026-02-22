<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;

$checks = [
    'FA/00005' => 'MOULDING PAKU B',
    'FA/00006' => 'MOULDING CUP OZ 12 OVAL',
    'FA/00007' => 'MOULDING OZ 16 DATAR',
    'FA/00008' => 'MOULD TUTUP GALON MERK RO',
    'FA/0005' => 'NONE', // Should be deleted
];

echo "=== Final SKU Sync Verification ===\n";
foreach ($checks as $sku => $expectedName) {
    if ($expectedName === 'NONE') {
        $count = Product::where('sku', $sku)->count();
        echo "[$sku]: " . ($count === 0 ? "REMOVED (PASS)" : "STILL EXISTS (FAIL)") . "\n";
        continue;
    }

    $product = Product::where('sku', $sku)->first();
    if ($product) {
        $actualName = $product->name;
        $status = (str_contains(strtolower($actualName), strtolower($expectedName)) || str_contains(strtolower($expectedName), strtolower($actualName))) ? "PASS" : "FAIL (Actual: $actualName)";
        echo "[$sku]: $status - $actualName\n";
    } else {
        echo "[$sku]: NOT FOUND (FAIL)\n";
    }
}

$faCount = Product::where('is_fixed_asset', true)->count();
echo "\nTotal Fixed Assets in DB: $faCount\n";
