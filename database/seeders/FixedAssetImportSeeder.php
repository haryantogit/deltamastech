<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Product;
use App\Models\Tag;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FixedAssetImportSeeder extends Seeder
{
    public function run(): void
    {
        $productCsv = 'D:\Program Receh\kledo\data-baru\produk_17-Feb-2026_halaman-1.csv';
        $files = [
            'D:\Program Receh\kledo\data-baru\aset-tetap_17-Feb-2026_halaman-1 (1).csv' => 'draft',
            'D:\Program Receh\kledo\data-baru\aset-tetap_17-Feb-2026_halaman-1 (2).csv' => 'disposed',
            'D:\Program Receh\kledo\data-baru\aset-tetap_17-Feb-2026_halaman-1.csv' => 'registered',
        ];

        // 1. Collect all potential assets from Product Master (The Sequence Boss)
        $productAssets = [];
        $reservedProductSkuNums = [];
        if (file_exists($productCsv)) {
            $handle = fopen($productCsv, 'r');
            fgetcsv($handle);
            while (($row = fgetcsv($handle)) !== false) {
                $sku = trim($row[1] ?? '');
                $name = trim($row[0] ?? '');
                if (str_starts_with($sku, 'FA/')) {
                    if ($sku === 'FA/0005')
                        $sku = 'FA/00005';
                    $skuNum = $this->extractSkuNum($sku);
                    $productAssets[] = [
                        'sku' => $sku,
                        'name' => $name,
                        'norm_name' => $this->normalizeName($name),
                        'description' => $row[5],
                        'buy_price' => $this->parseNumber($row[6]),
                        'orig_sku_num' => $skuNum,
                    ];
                    $reservedProductSkuNums[] = $skuNum;
                }
            }
            fclose($handle);
        }

        // 2. Collect all assets from Asset CSVs (The Financial Data source)
        $csvAssets = [];
        foreach ($files as $file => $status) {
            if (!file_exists($file))
                continue;
            $handle = fopen($file, 'r');
            fgetcsv($handle);
            while (($row = fgetcsv($handle)) !== false) {
                $name = trim($row[0]);
                if (empty($name))
                    continue;
                $sku = trim($row[1]);
                if ($sku === 'FA/0005')
                    $sku = 'FA/00005';

                $csvAssets[] = [
                    'original_sku' => $sku,
                    'name' => $name,
                    'norm_name' => $this->normalizeName($name),
                    'status' => $status,
                    'row' => $row,
                ];
            }
            fclose($handle);
        }

        // 3. Unify Assets
        $unifiedList = [];
        $processedCsvKeys = [];

        // Step 1: Items from Product Master
        foreach ($productAssets as $pAsset) {
            $match = null;
            // 1a. Try Name Match
            foreach ($csvAssets as $idx => $cAsset) {
                if (in_array($idx, $processedCsvKeys))
                    continue;
                if ($cAsset['norm_name'] === $pAsset['norm_name']) {
                    $match = $idx;
                    break;
                }
            }

            // 1b. Try Safe SKU Fallback Match (Only if names are somewhat similar)
            if ($match === null) {
                foreach ($csvAssets as $idx => $cAsset) {
                    if (in_array($idx, $processedCsvKeys))
                        continue;
                    if ($cAsset['original_sku'] === $pAsset['sku']) {
                        // Safe check: Is one name a subset of the other or very similar?
                        $pNorm = $pAsset['norm_name'];
                        $cNorm = $cAsset['norm_name'];
                        if (str_contains($cNorm, $pNorm) || str_contains($pNorm, $cNorm)) {
                            $match = $idx;
                            break;
                        }
                    }
                }
            }

            if ($match !== null) {
                $unifiedList[] = [
                    'name' => $pAsset['name'],
                    'description' => $csvAssets[$match]['row'][6] ?: $pAsset['description'],
                    'status' => 'registered', // Prioritize registered status if in product master
                    'row' => $csvAssets[$match]['row'],
                    'orig_sku_num' => $pAsset['orig_sku_num'],
                ];
                $processedCsvKeys[] = $match;
            } else {
                $unifiedList[] = [
                    'name' => $pAsset['name'],
                    'description' => $pAsset['description'],
                    'status' => 'registered',
                    'row' => null,
                    'buy_price' => $pAsset['buy_price'],
                    'orig_sku_num' => $pAsset['orig_sku_num'],
                ];
            }
        }

        // Step 2: Remaining Assets from CSVs (those not in product master)
        foreach ($csvAssets as $idx => $cAsset) {
            if (in_array($idx, $processedCsvKeys))
                continue;

            $skuNum = $this->extractSkuNum($cAsset['original_sku']);
            // If the SKU number is already taken by a Product item, push this one further back
            // so it doesn't displace the product sequence.
            if (in_array($skuNum, $reservedProductSkuNums) || $skuNum == 0) {
                $skuNum += 200; // Move to tail of the group
            }

            $unifiedList[] = [
                'name' => $cAsset['name'],
                'description' => $cAsset['row'][6],
                'status' => $cAsset['status'],
                'row' => $cAsset['row'],
                'orig_sku_num' => $skuNum,
            ];
            $processedCsvKeys[] = $idx;
        }

        // 4. Divide into groups: Registered, Disposed, Draft
        $registered = array_filter($unifiedList, fn($item) => $item['status'] === 'registered');
        $disposed = array_filter($unifiedList, fn($item) => $item['status'] === 'disposed');
        $draft = array_filter($unifiedList, fn($item) => $item['status'] === 'draft');

        // Sort by priority (Product Master order) within groups
        usort($registered, fn($a, $b) => $a['orig_sku_num'] <=> $b['orig_sku_num']);
        usort($disposed, fn($a, $b) => $a['orig_sku_num'] <=> $b['orig_sku_num']);

        // 5. Execution
        DB::beginTransaction();
        try {
            $existingAssets = Product::where('is_fixed_asset', true)->get();
            foreach ($existingAssets as $asset) {
                \App\Models\FixedAssetDepreciation::where('fixed_asset_id', $asset->id)->delete();
                $asset->tags()->detach();
                $asset->delete();
            }
            \App\Models\JournalEntry::where('reference_number', 'like', 'DEP-%')->delete();

            $currentSkuNum = 1;

            // 5a. Import Registered (Contiguous SKUs)
            foreach ($registered as $data) {
                $sku = 'FA/' . str_pad($currentSkuNum++, 5, '0', STR_PAD_LEFT);
                $this->processImportItem($sku, $data);
            }

            // 5b. Import Disposed (Continues following Registered SKUs)
            foreach ($disposed as $data) {
                $sku = 'FA/' . str_pad($currentSkuNum++, 5, '0', STR_PAD_LEFT);
                $this->processImportItem($sku, $data);
            }

            // 5c. Import Draft (NO SKU)
            foreach ($draft as $data) {
                $this->processImportItem(null, $data);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error("Error: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine());
        }
    }

    private function processImportItem($sku, $data)
    {
        if ($data['row']) {
            $this->importFullAsset($sku, $data['name'], $data['row'], $data['status']);
        } else {
            $this->createSimpleAsset($sku, $data['name'], $data['description'], $data['buy_price'] ?? 0);
        }
    }

    private function extractSkuNum($sku)
    {
        if (preg_match('/FA\/0*(\d+)/', $sku, $matches)) {
            return (int) $matches[1];
        }
        return 0;
    }

    private function importFullAsset($sku, $name, $row, $status)
    {
        $this->command->info("Importing: " . ($sku ?: "NO SKU") . " - $name ($status)");

        $purchaseDateStr = $row[2];
        $purchasePrice = $this->parseNumber($row[3]);
        $assetAccountCode = $row[4];
        $creditAccountCode = $row[5];
        $description = $row[6];
        $reference = $row[7];
        $noDepreciation = $row[8];
        $accumDepAccountCode = $row[9];
        $depExpenseAccountCode = $row[10];
        $depRate = $this->parseNumber($row[11]);
        $usefulLifeYears = $this->parseNumber($row[12]);
        if ($usefulLifeYears <= 0 && $depRate > 0)
            $usefulLifeYears = 100 / $depRate;
        $depStartDateStr = $row[14];
        $costLimit = $this->parseNumber($row[16]);
        $salvageValue = $this->parseNumber($row[17]);

        $purchaseDate = $this->parseDate($purchaseDateStr);
        $depStartDate = $this->parseDate($depStartDateStr);
        $hasDepreciation = (strcasecmp($noDepreciation, 'tidak') === 0);

        $assetAccount = Account::where('code', $assetAccountCode)->first();
        $creditAccount = Account::where('code', $creditAccountCode)->first();
        $accumDepAccount = Account::where('code', $accumDepAccountCode)->first();
        $depExpenseAccount = Account::where('code', $depExpenseAccountCode)->first();

        // Dep Calculation
        $depreciationRecords = [];
        $totalAccum = 0;
        if ($hasDepreciation && $depStartDate) {
            $elapsed = 0;
            $prevAccum = 0;
            $startDate = Carbon::parse($depStartDate);
            $totalMonths = $usefulLifeYears * 12;
            $loopDate = $startDate->copy()->startOfMonth();
            $currentDate = now()->endOfDay();
            while ($loopDate->lte($currentDate) && $elapsed < $totalMonths) {
                $elapsed++;
                $currRounded = (float) round(($purchasePrice - $salvageValue) * ($elapsed / $totalMonths));
                $monthly = $currRounded - $prevAccum;
                if ($monthly > 0) {
                    $depreciationRecords[] = ['period' => $loopDate->format('Y-m'), 'date' => $loopDate->copy()->endOfMonth(), 'amount' => $monthly];
                }
                $prevAccum = $currRounded;
                $loopDate->addMonthsNoOverflow(1);
            }
            $totalAccum = $prevAccum;
        }

        $product = Product::create([
            'sku' => $sku,
            'name' => $name,
            'description' => $description,
            'type' => 'fixed_asset',
            'is_fixed_asset' => true,
            'status' => $status,
            'purchase_date' => $purchaseDate,
            'purchase_price' => $purchasePrice,
            'has_depreciation' => $hasDepreciation,
            'asset_account_id' => $assetAccount?->id,
            'credit_account_id' => $creditAccount?->id,
            'accumulated_depreciation_account_id' => $accumDepAccount?->id,
            'depreciation_expense_account_id' => $depExpenseAccount?->id,
            'depreciation_rate' => $depRate,
            'useful_life_years' => (int) $usefulLifeYears,
            'useful_life_months' => ($usefulLifeYears - (int) $usefulLifeYears) * 12,
            'depreciation_method' => 'straight_line',
            'depreciation_start_date' => $depStartDate,
            'accumulated_depreciation_value' => round($totalAccum),
            'cost_limit' => $costLimit,
            'salvage_value' => $salvageValue,
            'reference' => $reference,
            'buy_price' => $purchasePrice,
            'cost_of_goods' => $purchasePrice,
            'is_active' => true,
        ]);

        if ($sku) {
            foreach ($depreciationRecords as $record) {
                $journal = \App\Models\JournalEntry::create([
                    'transaction_date' => $record['date']->format('Y-m-d'),
                    'reference_number' => 'DEP-' . $product->sku . '-' . $record['period'],
                    'description' => 'Penyusutan ' . $product->name . ' periode ' . $record['period'],
                    'total_amount' => $record['amount'],
                ]);
                \App\Models\JournalItem::create(['journal_entry_id' => $journal->id, 'account_id' => $depExpenseAccount?->id ?? 180, 'debit' => $record['amount'], 'credit' => 0]);
                \App\Models\JournalItem::create(['journal_entry_id' => $journal->id, 'account_id' => $accumDepAccount?->id ?? 181, 'debit' => 0, 'credit' => $record['amount']]);
                \App\Models\FixedAssetDepreciation::create(['fixed_asset_id' => $product->id, 'journal_entry_id' => $journal->id, 'period' => $record['period'], 'amount' => $record['amount']]);
            }
        }
    }

    private function createSimpleAsset($sku, $name, $description, $price)
    {
        Product::create([
            'sku' => $sku,
            'name' => $name,
            'description' => $description,
            'type' => 'product',
            'is_fixed_asset' => true,
            'status' => 'registered',
            'purchase_price' => $price,
            'buy_price' => $price,
            'cost_of_goods' => $price,
            'has_depreciation' => false,
            'is_active' => true,
        ]);
    }

    private function normalizeName($name)
    {
        $name = strtolower(trim($name));
        $name = str_replace('moulding', 'mould', $name);
        $name = preg_replace('/\s+/', ' ', $name);
        return $name;
    }

    private function parseNumber($str)
    {
        return (float) str_replace(',', '', $str);
    }

    private function parseDate($str)
    {
        if (empty($str))
            return null;
        try {
            return Carbon::createFromFormat('d/m/Y', $str)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }
}
