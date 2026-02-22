<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductBundle;
use App\Models\Category;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Models\Stock;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BundleProductSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $csvFile = base_path('data/produk-paket_23-Jan-2026_halaman-1.csv');

        if (!file_exists($csvFile)) {
            $this->command->error("CSV file not found: {$csvFile}");
            return;
        }

        $this->command->info("Reading Bundle CSV file: {$csvFile}");

        $file = fopen($csvFile, 'r');
        $headers = fgetcsv($file); // Skip header row

        $products = [];
        $importedCount = 0;

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
                    'description' => $row[5],
                    'sell_price' => $this->parseNumber($row[6]),
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

        $this->command->info("Found " . count($products) . " unique bundle products to import.");

        // Process Import
        DB::beginTransaction();
        try {
            foreach ($products as $sku => $data) {
                // Determine category and unit
                $category = Category::firstOrCreate(['name' => $data['category_name']]);
                $unit = Unit::firstOrCreate(['name' => $data['unit_name']]);

                // Create Product Bundle
                $product = Product::updateOrCreate(
                    ['sku' => $sku],
                    [
                        'name' => $data['name'],
                        'type' => 'bundle', // Set type to bundle
                        'category_id' => $category->id,
                        'unit_id' => $unit->id,
                        'description' => $data['description'],
                        'buy_price' => 0, // Bundle buy price calculated from components
                        'sell_price' => $data['sell_price'],
                        'stock' => 0, // Bundle stock is virtual, based on availability
                        'min_stock' => 0,
                    ]
                );

                // Initialize stock record (0 stock)
                $warehouse = Warehouse::where('code', 'UA')->first();
                Stock::firstOrCreate(
                    [
                        'product_id' => $product->id,
                        'warehouse_id' => $warehouse->id,
                    ],
                    ['quantity' => 0]
                );

                // Sync Components
                ProductBundle::where('bundle_id', $product->id)->delete();

                $totalCost = 0;

                foreach ($data['components'] as $comp) {
                    // Find component product by Name (or SKU)
                    $component = Product::where('name', $comp['name'])->first();

                    if (!$component) {
                        $component = Product::where('sku', $comp['name'])->first();
                    }

                    if ($component) {
                        ProductBundle::create([
                            'bundle_id' => $product->id,
                            'product_id' => $component->id,
                            'quantity' => $comp['quantity']
                        ]);

                        // Calculate estimated cost from HPP or Buy Price
                        $unitCost = $component->cost_of_goods > 0 ? $component->cost_of_goods : $component->buy_price;
                        $totalCost += ($unitCost * $comp['quantity']);
                    } else {
                        $this->command->warn("  ⚠ Component not found: '{$comp['name']}' for bundle {$sku}");
                    }
                }

                // Update Bundle HPP
                $product->cost_of_goods = $totalCost;
                $product->save();

                $importedCount++;
                $this->command->info("✓ Imported Bundle: {$data['name']} ({$sku}) with " . count($data['components']) . " components. Cost: " . number_format($totalCost, 2));
            }

            DB::commit();
            $this->command->info("\n=== Bundle Import Summary ===");
            $this->command->info("✓ Successfully imported: {$importedCount} bundles");
            $this->command->info("=============================\n");

        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error("Import failed: " . $e->getMessage());
        }
    }

    private function parseNumber($value)
    {
        if (empty($value) || !is_numeric(str_replace(',', '.', $value))) {
            return 0;
        }
        return (float) str_replace(',', '.', $value);
    }
}
