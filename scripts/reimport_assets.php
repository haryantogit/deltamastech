<?php

use App\Models\Product;
use App\Models\Tag;

function generateSku()
{
    $lastSku = Product::where('sku', 'like', 'FA/%')
        ->orderByRaw('CAST(SUBSTRING(sku, 4) AS UNSIGNED) DESC')
        ->first()?->sku;

    $nextId = 1;
    if ($lastSku && preg_match('/FA\/(\d+)/', $lastSku, $matches)) {
        $nextId = (int) $matches[1] + 1;
    } else {
        $nextId = (Product::where('is_fixed_asset', true)->max('id') ?? 0) + 1;
    }

    return 'FA/' . str_pad($nextId, 5, '0', STR_PAD_LEFT);
}

try {
    $sku1 = generateSku();
    $fa1 = Product::create([
        'name' => 'MESIN COUNTER PRODUK',
        'type' => 'service',
        'is_fixed_asset' => true,
        'status' => 'draft',
        'sku' => $sku1,
        'purchase_date' => '2025-06-15',
        'purchase_price' => 7000000,
        'buy_price' => 7000000,
        'cost_of_goods' => 7000000,
        'credit_account_id' => 178,
        'asset_account_id' => 179,
        'has_depreciation' => true,
        'is_active' => true,
    ]);
    $tag1 = Tag::firstOrCreate(['name' => 'BCA Operasional']);
    $fa1->tags()->attach($tag1->id);
    echo "Imported {$fa1->name} with SKU: {$sku1}\n";

    $sku2 = generateSku();
    $fa2 = Product::create([
        'name' => 'BNL VERTICAL MIXER',
        'type' => 'service',
        'is_fixed_asset' => true,
        'status' => 'draft',
        'sku' => $sku2,
        'purchase_date' => '2025-07-07',
        'purchase_price' => 16500000,
        'buy_price' => 16500000,
        'cost_of_goods' => 16500000,
        'credit_account_id' => 178,
        'asset_account_id' => 179,
        'has_depreciation' => true,
        'is_active' => true,
    ]);
    $tag2 = Tag::firstOrCreate(['name' => 'BNL VERTICAL MIXER']);
    $fa2->tags()->attach($tag2->id);
    echo "Imported {$fa2->name} with SKU: {$sku2}\n";

    echo "Re-import successful with SKUs\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
