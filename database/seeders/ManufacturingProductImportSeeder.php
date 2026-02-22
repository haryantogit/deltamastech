<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductMaterial;
use App\Models\ProductionCost;
use App\Models\Unit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ManufacturingProductImportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $csvFile = 'd:\\Program Receh\\kledo\\data-baru\\produk-manufaktur_17-Feb-2026_halaman-1.csv';

        if (!file_exists($csvFile)) {
            $this->command->error("CSV file not found: {$csvFile}");
            return;
        }

        $file = fopen($csvFile, 'r');
        $headers = fgetcsv($file); // Skip header row

        $products = [];

        // Step 1: Group rows by SKU
        while (($row = fgetcsv($file)) !== false) {
            if (empty($row[0]))
                continue;

            $sku = $row[2];

            if (!isset($products[$sku])) {
                $products[$sku] = [
                    'name' => $row[0],
                    'category_name' => $row[1],
                    'sku' => $sku,
                    'unit_name' => $row[3],
                    'description' => $row[5] ?? '',
                    'buy_price' => $this->parseNumber($row[6]),
                    'sell_price' => $this->parseNumber($row[9]),
                    'purchase_account_code' => $row[7] ?? '',
                    'sales_account_code' => $row[13] ?? '',
                    'inventory_account_code' => $row[15] ?? '',
                    'min_stock' => $this->parseNumber($row[16] ?? 0),
                    'materials' => [],
                    'costs' => []
                ];
            }

            // Material data (BOM)
            $materialName = $row[17] ?? '';
            $materialQty = $this->parseNumber($row[18] ?? 0);

            if (!empty($materialName) && $materialQty > 0) {
                $products[$sku]['materials'][] = [
                    'name' => $materialName,
                    'quantity' => $materialQty
                ];
            }

            // Production Cost data
            $costAccountCode = $row[19] ?? '';
            $costAmount = $this->parseNumber($row[20] ?? 0);

            if (!empty($costAccountCode) && $costAmount > 0) {
                $products[$sku]['costs'][] = [
                    'account_code' => $costAccountCode,
                    'amount' => $costAmount
                ];
            }
        }
        fclose($file);

        $this->command->info("Found " . count($products) . " manufacturing products to sync.");

        // Step 2: Process sync
        DB::beginTransaction();
        try {
            foreach ($products as $sku => $data) {
                $category = Category::firstOrCreate(['name' => $data['category_name']]);
                $unit = Unit::firstOrCreate(['name' => $data['unit_name']]);

                // Find Accounts
                $purchaseAccount = Account::where('code', $data['purchase_account_code'])->first();
                $salesAccount = Account::where('code', $data['sales_account_code'])->first();
                $inventoryAccount = Account::where('code', $data['inventory_account_code'])->first();

                $product = Product::updateOrCreate(
                    ['sku' => $sku],
                    [
                        'name' => $data['name'],
                        'slug' => Str::slug($data['name']),
                        'type' => 'manufacturing',
                        'category_id' => $category->id,
                        'unit_id' => $unit->id,
                        'unit_name' => $data['unit_name'],
                        'description' => $data['description'],
                        'buy_price' => 0,
                        'sell_price' => $data['sell_price'],
                        'purchase_account_id' => $purchaseAccount?->id,
                        'sales_account_id' => $salesAccount?->id,
                        'inventory_account_id' => $inventoryAccount?->id,
                        'min_stock' => $data['min_stock'],
                        'can_be_purchased' => false,
                        'can_be_sold' => true,
                        'track_inventory' => true,
                        'is_active' => true,
                        'is_fixed_asset' => false,
                        'status' => 'registered',
                    ]
                );

                // Sync Materials
                ProductMaterial::where('product_id', $product->id)->delete();
                $totalMaterialCost = 0;

                foreach ($data['materials'] as $mat) {
                    $material = Product::where('name', $mat['name'])->first()
                        ?? Product::where('sku', $mat['name'])->first();

                    if ($material) {
                        ProductMaterial::create([
                            'product_id' => $product->id,
                            'material_id' => $material->id,
                            'quantity' => $mat['quantity']
                        ]);
                        $unitCost = $material->cost_of_goods > 0 ? $material->cost_of_goods : $material->buy_price;
                        $totalMaterialCost += ($unitCost * $mat['quantity']);
                    } else {
                        $this->command->warn("  ⚠ Material not found: '{$mat['name']}' for product {$sku}");
                    }
                }

                // Sync Production Costs
                ProductionCost::where('product_id', $product->id)->delete();
                $totalProductionCost = 0;

                foreach ($data['costs'] as $cost) {
                    $account = Account::where('code', $cost['account_code'])->first();
                    if ($account) {
                        ProductionCost::create([
                            'product_id' => $product->id,
                            'account_id' => $account->id,
                            'amount' => $cost['amount'],
                            'description' => 'Biaya Produksi'
                        ]);
                        $totalProductionCost += $cost['amount'];
                    } else {
                        $this->command->warn("  ⚠ Account not found: '{$cost['account_code']}' for product {$sku}");
                    }
                }

                // Final HPP Calculation
                $product->cost_of_goods = $totalMaterialCost + $totalProductionCost;
                $product->save();

                $this->command->info("✓ Synced: {$data['name']} (HPP: " . number_format($product->cost_of_goods, 2) . ")");
            }

            DB::commit();
            $this->command->info("\nImport Success!");
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
