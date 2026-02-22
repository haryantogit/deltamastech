<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductMaterial;
use App\Models\Category;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Models\Stock;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ManufacturingProductSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $csvFile = base_path('data/produk-manufaktur_23-Jan-2026_halaman-1.csv');

        if (!file_exists($csvFile)) {
            $this->command->error("CSV file not found: {$csvFile}");
            return;
        }

        $this->command->info("Reading Manufacturing CSV file: {$csvFile}");

        $file = fopen($csvFile, 'r');
        $headers = fgetcsv($file); // Skip header row

        $products = [];
        $importedCount = 0;

        // Group rows by SKU first to handle BOM
        while (($row = fgetcsv($file)) !== false) {
            if (empty($row[0]))
                continue;

            $sku = $row[2]; // SKU Produk Manufaktur

            if (!isset($products[$sku])) {
                $products[$sku] = [
                    'name' => $row[0],
                    'category_name' => $row[1],
                    'sku' => $sku,
                    'unit_name' => $row[3],
                    'description' => $row[5],
                    'buy_price' => $this->parseNumber($row[6]), // Usually empty for manuf
                    'sell_price' => $this->parseNumber($row[9]),
                    'min_stock' => $this->parseNumber($row[16] ?? 0),
                    'materials' => []
                ];
            }

            // Material data
            $materialName = $row[17] ?? ''; // Nama (SKU) Bahan Produk
            $materialQty = $this->parseNumber($row[18] ?? 0);

            if (!empty($materialName) && $materialQty > 0) {
                $products[$sku]['materials'][] = [
                    'name' => $materialName,
                    'quantity' => $materialQty
                ];
            }
        }
        fclose($file);

        $this->command->info("Found " . count($products) . " unique manufacturing products to import.");

        // Process Import
        DB::beginTransaction();
        try {
            foreach ($products as $sku => $data) {
                // Determine category and unit
                $category = Category::firstOrCreate(['name' => $data['category_name']]);
                $unit = Unit::firstOrCreate(['name' => $data['unit_name']]);

                // Create Product Manufaktur
                $product = Product::updateOrCreate(
                    ['sku' => $sku],
                    [
                        'name' => $data['name'],
                        'type' => 'manufacturing', // Set type to manufacturing
                        'category_id' => $category->id,
                        'unit_id' => $unit->id,
                        'description' => $data['description'],
                        'buy_price' => 0, // Manuf product buy price usually 0 or calc from BOM
                        'sell_price' => $data['sell_price'],
                        'stock' => 0, // Initial stock 0
                        'min_stock' => $data['min_stock'],
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

                // Sync Materials (BOM)
                // First, remove existing materials to avoid dupes if re-running
                ProductMaterial::where('product_id', $product->id)->delete();

                $totalCost = 0;

                foreach ($data['materials'] as $mat) {
                    // Find material product by Name (or SKU if needed)
                    // The CSV says "Nama (SKU) Bahan Produk", but mostly it seems to be Name.
                    // Let's try to find by Name first.
                    $material = Product::where('name', $mat['name'])->first();

                    if (!$material) {
                        // Try finding by SKU if name fails (assuming mat name might be SKU)
                        $material = Product::where('sku', $mat['name'])->first();
                    }

                    if ($material) {
                        ProductMaterial::create([
                            'product_id' => $product->id,
                            'material_id' => $material->id,
                            'quantity' => $mat['quantity']
                        ]);

                        // Calculate estimated cost
                        // Material Cost = Material Qty * Material Buy Price (or Cost)
                        // Use HPP (cost_of_goods) if available, else buy_price
                        $unitCost = $material->cost_of_goods > 0 ? $material->cost_of_goods : $material->buy_price;
                        $totalCost += ($unitCost * $mat['quantity']);
                    } else {
                        $this->command->warn("  ⚠ Material not found: '{$mat['name']}' for product {$sku}");
                    }
                }

                // Update Product HPP based on BOM
                $product->cost_of_goods = $totalCost;
                $product->save();

                $importedCount++;
                $this->command->info("✓ Imported Manuf: {$data['name']} ({$sku}) with " . count($data['materials']) . " materials. Cost: " . number_format($totalCost, 2));
            }

            DB::commit();
            $this->command->info("\n=== Manufacturing Import Summary ===");
            $this->command->info("✓ Successfully imported: {$importedCount} products");
            $this->command->info("==================================\n");

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
