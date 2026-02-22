<?php

use App\Models\Product;
use App\Models\Tag;

try {
    $fa1 = Product::create([
        'name' => 'MESIN COUNTER PRODUK',
        'type' => 'service',
        'is_fixed_asset' => true,
        'status' => 'draft',
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

    $fa2 = Product::create([
        'name' => 'BNL VERTICAL MIXER',
        'type' => 'service',
        'is_fixed_asset' => true,
        'status' => 'draft',
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

    echo "Import successful\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
