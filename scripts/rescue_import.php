<?php

use App\Models\Product;
use Filament\Actions\Imports\Models\FailedImportRow;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$lastImport = Import::latest()->first();

if (!$lastImport) {
    echo "No import found.\n";
    exit;
}

echo "Processing failed rows for Import ID: " . $lastImport->id . "\n";

$failedRows = FailedImportRow::where('import_id', $lastImport->id)->cursor();

$count = 0;
$errors = 0;

foreach ($failedRows as $row) {
    try {
        $data = $row->data;

        // processing logic
        $type = str_contains($data['*Kategori Produk'] ?? '', 'Jasa') ? 'service' : 'standard';

        $productData = [
            'name' => $data['*Nama Produk'] ?? 'Unknown',
            'sku' => $data['Kode / SKU Produk'] ?? null,
            'type' => $type,
            'buy_price' => is_numeric($data['Harga Pembelian'] ?? '') ? $data['Harga Pembelian'] : 0,
            'sell_price' => is_numeric($data['Harga Penjualan'] ?? '') ? $data['Harga Penjualan'] : 0,
            'cost_of_goods' => is_numeric($data['Harga Pokok Penjualan (HPP)'] ?? '') ? $data['Harga Pokok Penjualan (HPP)'] : 0,
            'stock' => is_numeric($data['Kuantitas'] ?? '') ? $data['Kuantitas'] : 0,
            'min_stock' => is_numeric($data['Stok Minimal'] ?? '') ? $data['Stok Minimal'] : 0,
            'unit' => $data['*Unit Produk (Satuan)'] ?? null,
        ];

        // Skip if SKU is missing? The importer used 'sku' as key.
        if (empty($productData['sku'])) {
            // maybe generate one or skip? 
            // The CSV seems to have SKUs for everything.
            echo "Skipping row with missing SKU: " . $productData['name'] . "\n";
            continue;
        }

        Product::updateOrCreate(
            ['sku' => $productData['sku']],
            $productData
        );

        $count++;
        // Optional: delete the failed row if successful?
        // $row->delete(); 

        if ($count % 50 == 0)
            echo "Processed $count rows...\n";

    } catch (\Exception $e) {
        $errors++;
        echo "Error processing row: " . $e->getMessage() . "\n";
    }
}

echo "Done. Successfully processed: $count. Errors: $errors.\n";
