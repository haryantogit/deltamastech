<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Product;
use App\Models\Category;
use App\Models\Unit;
use App\Models\Tax;

class ImportProductsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $csvFile = 'D:\Program Receh\kledo\data\produk_23-Jan-2026_halaman-1.csv';

        if (!file_exists($csvFile)) {
            $this->command->error("File not found: $csvFile");
            return;
        }

        $file = fopen($csvFile, 'r');
        $header = fgetcsv($file); // Skip header

        // Map header to index
        $headerMap = [];
        foreach ($header as $index => $column) {
            $headerMap[trim($column)] = $index;
        }

        $count = 0;
        DB::beginTransaction();
        try {
            while (($row = fgetcsv($file)) !== false) {
                // Helper to get value by column name
                $getVal = fn($col) => isset($headerMap[$col]) ? trim($row[$headerMap[$col]]) : null;

                $name = $getVal('*Nama Produk');
                $sku = $getVal('Kode / SKU Produk');

                if (empty($name))
                    continue;

                $this->command->info("Processing: $name ($sku)");

                // 1. Category
                $categoryName = $getVal('*Kategori Produk');
                $categoryId = null;
                if (!empty($categoryName)) {
                    $category = Category::firstOrCreate(['name' => $categoryName]);
                    $categoryId = $category->id;
                }

                // 2. Unit
                $unitName = $getVal('*Unit Produk (Satuan)');
                $unitId = null;
                if (!empty($unitName)) {
                    $unit = Unit::firstOrCreate(['name' => $unitName]);
                    $unitId = $unit->id;
                }

                // 3. Type Determination
                $type = 'standard';
                $isFixedAsset = false;

                if (stripos($categoryName, 'Jasa') !== false) {
                    $type = 'service';
                } elseif (stripos($categoryName, 'Aset Tetap') !== false) {
                    $isFixedAsset = true;
                }

                // 4. Create/Update Product
                Product::updateOrCreate(
                    ['sku' => $sku], // Match by SKU
                    [
                        'name' => $name,
                        'category_id' => $categoryId,
                        'unit_name' => $unitName, // Keep name for reference if needed
                        'unit_id' => $unitId,
                        'description' => $getVal('Deskripsi Produk'),
                        'buy_price' => $this->parseNumber($getVal('Harga Pembelian')),
                        'sell_price' => $this->parseNumber($getVal('Harga Penjualan')),
                        'type' => $type,
                        'is_fixed_asset' => $isFixedAsset,
                        'track_inventory' => ($type === 'standard'), // Track stock for standard products
                        'stock' => $this->parseNumber($getVal('Kuantitas')), // Initial stock (migrated)
                        'min_stock' => $this->parseNumber($getVal('Stok Minimal')),
                        'cost_of_goods' => $this->parseNumber($getVal('Harga Pokok Penjualan (HPP)')),
                        // 'is_active' => true, // Default to true
                    ]
                );
                $count++;
            }
            DB::commit();
            $this->command->info("Imported $count products successfully.");
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error("Error: " . $e->getMessage());
            Log::error("Product Import Error: " . $e->getMessage());
        }

        fclose($file);
    }

    private function parseNumber($value)
    {
        if (empty($value))
            return 0;
        // Handle thousands separator if needed, but CSV seems to use standard numbers or dot decimals
        return (float) $value;
    }
}
