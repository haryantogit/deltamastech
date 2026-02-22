<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class StandardProductImportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $csvFile = 'd:\\Program Receh\\kledo\\data-baru\\produk_17-Feb-2026_halaman-1.csv';

        if (!file_exists($csvFile)) {
            $this->command->error("CSV file not found: {$csvFile}");
            return;
        }

        $file = fopen($csvFile, 'r');
        $header = fgetcsv($file); // Skip header row

        $imported = 0;
        $skipped = 0;
        $errors = [];

        while (($row = fgetcsv($file)) !== false) {
            try {
                // Skip empty rows
                if (empty($row[0])) {
                    continue;
                }

                $productData = [
                    'name' => $row[0],
                    'sku' => $row[1],
                    'category_name' => $row[2],
                    'unit_name' => $row[4],
                    'description' => $row[5] ?? '',
                    'buy_price' => $this->parseNumber($row[6]),
                    'purchase_account_code' => $row[7],
                    'sell_price' => $this->parseNumber($row[9]),
                    'sales_account_code' => $row[13],
                    'inventory_account_code' => $row[15],
                    'min_stock' => $this->parseNumber($row[16]),
                    'stock' => $this->parseNumber($row[17]),
                    'cost_of_goods' => $this->parseNumber($row[18]),
                ];

                // Check if product already exists
                if (Product::where('sku', $productData['sku'])->exists()) {
                    $this->command->warn("Skipping duplicate SKU: {$productData['sku']}");
                    $skipped++;
                    continue;
                }

                // Get or create category
                $category = null;
                if (!empty($productData['category_name'])) {
                    $category = Category::firstOrCreate(
                        ['name' => $productData['category_name']]
                    );
                }

                // Get or create unit
                $unit = null;
                if (!empty($productData['unit_name'])) {
                    $unit = Unit::firstOrCreate(
                        ['name' => $productData['unit_name']]
                    );
                }

                // Find accounts by code
                $purchaseAccount = $this->findAccountByCode($productData['purchase_account_code']);
                $salesAccount = $this->findAccountByCode($productData['sales_account_code']);
                $inventoryAccount = $this->findAccountByCode($productData['inventory_account_code']);

                $type = 'standard';

                $productAttributes = [
                    'name' => $productData['name'],
                    'sku' => $productData['sku'],
                    'slug' => Str::slug($productData['name']),
                    'description' => $productData['description'],
                    'type' => $type,
                    'unit_name' => $productData['unit_name'],
                    'buy_price' => $productData['buy_price'] ?? 0,
                    'sell_price' => $productData['sell_price'] ?? 0,
                    'cost_of_goods' => $productData['cost_of_goods'] ?? 0,
                    'stock' => $productData['stock'] ?? 0,
                    'min_stock' => $productData['min_stock'] ?? 0,
                    'can_be_purchased' => true,
                    'can_be_sold' => true,
                    'track_inventory' => true,
                    'is_active' => true,
                    'is_fixed_asset' => false,
                ];

                if ($category) {
                    $productAttributes['category_id'] = $category->id;
                }
                if ($unit) {
                    $productAttributes['unit_id'] = $unit->id;
                }
                if ($purchaseAccount) {
                    $productAttributes['purchase_account_id'] = $purchaseAccount->id;
                }
                if ($salesAccount) {
                    $productAttributes['sales_account_id'] = $salesAccount->id;
                }
                if ($inventoryAccount) {
                    $productAttributes['inventory_account_id'] = $inventoryAccount->id;
                }

                Product::create($productAttributes);

                $imported++;
                $this->command->info("Imported: {$productData['name']} ({$productData['sku']})");

            } catch (\Exception $e) {
                $errorMsg = "Row: {$row[0]} ({$row[1]}) - {$e->getMessage()}";
                $errors[] = $errorMsg;
                $this->command->error($errorMsg);
            }
        }

        fclose($file);

        $this->command->info("\n=== Import Summary ===");
        $this->command->info("Imported: {$imported}");
        $this->command->info("Skipped: {$skipped}");
        $this->command->info("Errors: " . count($errors));
    }

    private function parseNumber($value): ?float
    {
        if (empty($value)) {
            return null;
        }
        $cleaned = preg_replace('/[^0-9.]/', '', $value);
        return $cleaned ? (float) $cleaned : null;
    }

    private function findAccountByCode(?string $code): ?Account
    {
        if (empty($code)) {
            return null;
        }
        return Account::where('code', $code)->first();
    }
}
