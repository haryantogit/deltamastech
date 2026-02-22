<?php

use App\Models\Product;
use App\Models\Category;
use App\Models\Unit;
use App\Models\Account;
use App\Models\ProductMaterial;
use App\Models\ProductionCost;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

// Path to CSV
$csvFile = 'D:\Program Receh\kledo\data\produk-manufaktur_23-Jan-2026_halaman-1.csv';

if (!file_exists($csvFile)) {
    echo "File not found: $csvFile\n";
    exit;
}

$file = fopen($csvFile, 'r');
$header = fgetcsv($file); // Skip header

// Strategy: Group by SKU first to handle header data, then process rows for ingredients/costs.
$groupedData = [];

echo "Reading CSV...\n";
while (($data = fgetcsv($file)) !== false) {
    $sku = $data[2] ?? null;
    if (!$sku)
        continue;

    if (!isset($groupedData[$sku])) {
        $groupedData[$sku] = [
            'info' => $data,
            'items' => []
        ];
    }
    $groupedData[$sku]['items'][] = $data;
}
fclose($file);

echo "Found " . count($groupedData) . " manufacturing products to process.\n";

DB::beginTransaction();

try {
    foreach ($groupedData as $sku => $group) {
        $info = $group['info'];
        $items = $group['items'];

        $name = $info[0];
        $categoryName = $info[1];
        $unitName = $info[3];
        $desc = $info[5];

        $sellPrice = floatval(str_replace(['Rp', ' '], '', $info[9] ?? 0));
        $salesAccountCode = $info[13] ?? null;
        $inventoryAccountCode = $info[15] ?? null;
        $minStock = floatval($info[16] ?? 0);

        echo "Processing $name ($sku)...\n";

        // 1. Create/Update Product
        $category = $categoryName ? Category::firstOrCreate(['name' => $categoryName]) : null;
        $unit = $unitName ? Unit::firstOrCreate(['name' => $unitName]) : null;

        $salesAccount = $salesAccountCode ? Account::where('code', $salesAccountCode)->first() : null;
        $inventoryAccount = $inventoryAccountCode ? Account::where('code', $inventoryAccountCode)->first() : null;

        $product = Product::where('sku', $sku)->first();
        if (!$product) {
            $product = new Product();
            $product->sku = $sku;
        }

        $product->name = $name;
        $product->type = 'manufacturing';
        $product->category_id = $category?->id;
        $product->unit_id = $unit?->id;
        $product->description = $desc;
        $product->sell_price = $sellPrice;
        $product->sales_account_id = $salesAccount?->id;
        $product->inventory_account_id = $inventoryAccount?->id;
        $product->min_stock = $minStock;
        $product->is_active = true;
        $product->save();

        // 2. Clear existing relationships
        DB::table('product_materials')->where('product_id', $product->id)->delete();
        DB::table('production_costs')->where('product_id', $product->id)->delete();

        // 3. Process Items (Ingredients & Costs)
        foreach ($items as $item) {
            $ingredientName = $item[17] ?? null;
            $ingredientQty = floatval($item[18] ?? 0);

            $costAccountCode = $item[19] ?? null;
            $costAmount = floatval($item[20] ?? 0);

            // Handle Ingredient
            if ($ingredientName && $ingredientQty > 0) {
                $ingredient = Product::where('name', $ingredientName)->first();
                if (!$ingredient) {
                    $ingredient = Product::where('sku', $ingredientName)->first();
                }

                if ($ingredient) {
                    $product->materials()->attach($ingredient->id, ['quantity' => $ingredientQty]);
                } else {
                    echo "  WARNING: Ingredient not found: $ingredientName\n";
                }
            }

            // Handle Production Cost
            if ($costAccountCode && $costAmount > 0) {
                $costAccount = Account::where('code', $costAccountCode)->first();
                if ($costAccount) {
                    DB::table('production_costs')->insert([
                        'product_id' => $product->id,
                        'account_id' => $costAccount->id,
                        'name' => $costAccount->name,
                        'amount' => $costAmount,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } else {
                    echo "  WARNING: Cost Account not found: $costAccountCode\n";
                }
            }
        }
    }

    DB::commit();
    echo "Manufacturing Import Completed Successfully!\n";

} catch (\Exception $e) {
    DB::rollBack();
    echo "ERROR: " . $e->getMessage() . "\n";
}
