<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;

$checks = [
    'FA/00007' => 'MOULDING OZ 16 DATAR',
    'FA/00008' => 'MOULD TUTUP GALON MERK RO',
    'FA/00035' => 'MOLD TUTUP GELAS TW OZ 12 POLOS 1 CAVITY',
    'FA/00038' => 'MOULD PAKU S PALANG 16 CAVITY',
    'FA/00080' => 'MOULDING TUTUP FLIP 2 CAVITY',
    'FA/00001' => 'MESIN INJECTION 80T EASY MASTER TH 2011', // Should keep asset name if not in products, oh wait, mesins are NOT in produk csv? I should check
];

echo "=== Sync Verification ===\n";
foreach ($checks as $sku => $expectedName) {
    $product = Product::where('sku', $sku)->first();
    if ($product) {
        $actualName = $product->name;
        $status = ($actualName === $expectedName) ? "PASS" : "FAIL (Actual: $actualName)";
        echo "[$sku]: $status\n";
    } else {
        echo "[$sku]: NOT FOUND\n";
    }
}

$faCount = Product::where('is_fixed_asset', true)->count();
echo "\nTotal Fixed Assets in DB: $faCount\n";
