<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Category;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Models\Stock;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $csvFile = base_path('data/produk_23-Jan-2026_halaman-1.csv');

        if (!file_exists($csvFile)) {
            $this->command->error("CSV file not found: {$csvFile}");
            return;
        }

        $this->command->info("Reading CSV file: {$csvFile}");

        // Read CSV
        $file = fopen($csvFile, 'r');
        $headers = fgetcsv($file); // Skip header row

        $imported = 0;
        $failed = 0;

        // Get or create Unassigned warehouse
        $warehouse = Warehouse::firstOrCreate(
            ['code' => 'UA'],
            ['name' => 'Unassigned']
        );

        DB::beginTransaction();

        try {
            while (($row = fgetcsv($file)) !== false) {
                try {
                    // Skip empty rows
                    if (empty($row[0])) {
                        continue;
                    }

                    // Map CSV columns to array keys
                    $data = [
                        'name' => $row[0] ?? '',
                        'sku' => $row[1] ?? '',
                        'category_name' => $row[2] ?? '',
                        'image' => $row[3] ?? '',
                        'unit_name' => $row[4] ?? '',
                        'description' => $row[5] ?? '',
                        'buy_price' => $this->parseNumber($row[6] ?? 0),
                        'account_purchase' => $row[7] ?? '',
                        'tax_purchase' => $row[8] ?? '',
                        'sell_price' => $this->parseNumber($row[9] ?? 0),
                        'min_wholesale' => $row[10] ?? '',
                        'wholesale_price' => $row[11] ?? '',
                        'wholesale_percent' => $row[12] ?? '',
                        'account_sales' => $row[13] ?? '',
                        'tax_sales' => $row[14] ?? '',
                        'account_inventory' => $row[15] ?? '',
                        'min_stock' => $this->parseNumber($row[16] ?? 0),
                        'stock' => $this->parseNumber($row[17] ?? 0),
                        'cost_of_goods' => $this->parseNumber($row[18] ?? 0),
                    ];

                    // Determine product type
                    $type = 'standard';
                    if (stripos($data['category_name'], 'Jasa') !== false) {
                        $type = 'service';
                    }

                    // Create or get category
                    $category = null;
                    if (!empty($data['category_name'])) {
                        $category = Category::firstOrCreate(['name' => $data['category_name']]);
                    }

                    // Create or get unit
                    $unit = null;
                    if (!empty($data['unit_name'])) {
                        $unit = Unit::firstOrCreate(['name' => $data['unit_name']]);
                    }

                    // Calculate HPP if not provided
                    $hpp = $data['cost_of_goods'];
                    if (empty($hpp) || $hpp == 0) {
                        $hpp = $data['stock'] * $data['buy_price'];
                    }

                    // Create or update product
                    $product = Product::updateOrCreate(
                        ['sku' => $data['sku']],
                        [
                            'name' => $data['name'],
                            'type' => $type,
                            'buy_price' => $data['buy_price'],
                            'sell_price' => $data['sell_price'],
                            'cost_of_goods' => $hpp,
                            'stock' => $data['stock'],
                            'min_stock' => $data['min_stock'],
                            'unit_id' => $unit?->id,
                            'category_id' => $category?->id,
                        ]
                    );

                    // Create or update stock in Unassigned warehouse
                    Stock::updateOrCreate(
                        [
                            'product_id' => $product->id,
                            'warehouse_id' => $warehouse->id,
                        ],
                        [
                            'quantity' => $data['stock'],
                        ]
                    );

                    $imported++;
                    $this->command->info("✓ Imported: {$data['name']} ({$data['sku']})");

                } catch (\Exception $e) {
                    $failed++;
                    $this->command->error("✗ Failed to import row: " . $e->getMessage());
                    Log::error("Product import failed", [
                        'row' => $row,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            DB::commit();
            fclose($file);

            $this->command->info("\n=== Import Summary ===");
            $this->command->info("✓ Successfully imported: {$imported} products");
            if ($failed > 0) {
                $this->command->warn("✗ Failed: {$failed} products");
            }
            $this->command->info("======================\n");

        } catch (\Exception $e) {
            DB::rollBack();
            fclose($file);
            $this->command->error("Import failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Parse number from string (handles commas and decimals)
     */
    private function parseNumber($value)
    {
        if (empty($value) || !is_numeric(str_replace(',', '.', $value))) {
            return 0;
        }

        // Replace comma with dot for decimal
        $value = str_replace(',', '.', $value);

        return (float) $value;
    }
}
