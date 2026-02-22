<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductBundle;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Models\Stock;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BundleProductImportSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $csvFile = 'd:\\Program Receh\\kledo\\data-baru\\produk-paket_17-Feb-2026_halaman-1.csv';

        if (!file_exists($csvFile)) {
            $this->command->error("CSV file not found: {$csvFile}");
            return;
        }

        $this->command->info("Reading Bundle CSV file: {$csvFile}");

        $file = fopen($csvFile, 'r');
        $headers = fgetcsv($file); // Skip header row

        $products = [];

        // Group rows by SKU first to handle Bundles
        while (($row = fgetcsv($file)) !== false) {
            if (empty($row[0]))
                continue;

            $sku = $row[2]; // SKU Produk Paket

            if (!isset($products[$sku])) {
                $products[$sku] = [
                    'name' => $row[0],
                    'category_name' => $row[1],
                    'sku' => $sku,
                    'unit_name' => $row[3],
                    'description' => $row[5] ?? '',
                    'sell_price' => $this->parseNumber($row[6]),
                    'sales_account_code' => $row[10] ?? '',
                    'components' => []
                ];
            }

            // Component data
            $componentName = $row[12] ?? ''; // Nama / SKU Produk
            $componentQty = $this->parseNumber($row[13] ?? 0);

            if (!empty($componentName) && $componentQty > 0) {
                $products[$sku]['components'][] = [
                    'name' => $componentName,
                    'quantity' => $componentQty
                ];
            }
        }
        fclose($file);

        $this->command->info("Found " . count($products) . " unique bundle products to sync.");

        // Process Import
        DB::beginTransaction();
        try {
            foreach ($products as $sku => $data) {
                // Determine category and unit
                $category = Category::firstOrCreate(['name' => $data['category_name']]);
                $unit = Unit::firstOrCreate(['name' => $data['unit_name']]);

                // Find Accounts
                $salesAccount = Account::where('code', $data['sales_account_code'])->first();
                $purchaseAccount = Account::where('code', '5-50000')->first(); // Default for bundles
                $inventoryAccount = Account::where('code', '1-10200')->first(); // Default for bundles

                $product = Product::updateOrCreate(
                    ['sku' => $sku],
                    [
                        'name' => $data['name'],
                        'slug' => Str::slug($data['name']),
                        'type' => 'bundle',
                        'category_id' => $category->id,
                        'unit_id' => $unit->id,
                        'unit_name' => $data['unit_name'],
                        'description' => $data['description'],
                        'buy_price' => 0,
                        'sell_price' => $data['sell_price'],
                        'purchase_account_id' => $purchaseAccount?->id,
                        'sales_account_id' => $salesAccount?->id,
                        'inventory_account_id' => $inventoryAccount?->id,
                        'stock' => 0,
                        'min_stock' => 0,
                        'can_be_sold' => true,
                        'track_inventory' => true,
                        'is_active' => true,
                        'is_fixed_asset' => false,
                        'status' => 'registered',
                    ]
                );

                // Sync Components
                ProductBundle::where('product_id', $product->id)->delete();

                $totalCost = 0;

                foreach ($data['components'] as $comp) {
                    $component = Product::where('name', $comp['name'])->first()
                        ?? Product::where('sku', $comp['name'])->first();

                    if ($component) {
                        ProductBundle::create([
                            'product_id' => $product->id,
                            'item_id' => $component->id,
                            'quantity' => $comp['quantity']
                        ]);

                        $unitCost = $component->cost_of_goods > 0 ? $component->cost_of_goods : $component->buy_price;
                        $totalCost += ($unitCost * $comp['quantity']);
                    } else {
                        $this->command->warn("  ⚠ Component not found: '{$comp['name']}' for bundle {$sku}");
                    }
                }

                // Update Bundle HPP
                $product->cost_of_goods = $totalCost;
                $product->save();

                $this->command->info("✓ Synced Bundle: {$data['name']} ({$sku}) with " . count($data['components']) . " components. HPP: " . number_format($totalCost, 2));
            }

            DB::commit();
            $this->command->info("\nBundle Import Success!");

        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error("Import failed: " . $e->getMessage());
        }
    }

    private function parseNumber($value)
    {
        if (empty($value))
            return 0;
        $cleaned = preg_replace('/[^0-9.]/', '', str_replace(',', '.', $value));
        return $cleaned ? (float) $cleaned : 0;
    }
}
