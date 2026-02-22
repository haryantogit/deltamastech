<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Product;

$product = Product::where('name', 'KARUNG')->first();

if ($product) {
    echo "Product: {$product->name}\n";
    echo "ID: {$product->id}\n";
    echo "Track Inventory: " . ($product->track_inventory ? 'YES' : 'NO') . "\n";
    echo "Current Stock: {$product->stock}\n";
} else {
    echo "Product 'KARUNG' not found.\n";
}
