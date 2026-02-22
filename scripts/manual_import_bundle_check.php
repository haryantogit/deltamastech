<?php

use App\Models\Product;
use App\Models\Category;
use App\Models\Unit;
use App\Models\Account;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

// Path to CSV
$csvFile = 'D:\Program Receh\kledo\data\produk-paket_23-Jan-2026_halaman-1.csv';

if (!file_exists($csvFile)) {
    echo "File not found: $csvFile\n";
    exit;
}

$file = fopen($csvFile, 'r');
$header = fgetcsv($file); // Skip header

// *Nama Produk Paket (0), *Kategori (1), *Kode SKU (2), *Satuan (3), URL (4), Deskripsi (5), 
// Harga Produk Paket (6), Min Grosir (7), Harga Grosir (8), Harga Grosir % (9), 
// *Kode Akun Penjualan (10), Pajak (11)
// *Nama / SKU Produk (12), *Jumlah Produk (13)

$groupedData = [];

echo "Reading CSV...\n";
while (($data = fgetcsv($file)) !== false) {
    // Skip empty lines or lines without SKU
    $sku = $data[2] ?? null;
    if (!$sku)
        continue;

    // Group by SKU
    if (!isset($groupedData[$sku])) {
        $groupedData[$sku] = [
            'info' => $data,
            'items' => []
        ];
    }
    // Add item entry
    $groupedData[$sku]['items'][] = [
        'item_identifier' => $data[12] ?? null,
        'quantity' => floatval($data[13] ?? 0)
    ];
}
fclose($file);

echo "Found " . count($groupedData) . " bundles to process.\n";

DB::beginTransaction();

try {
    foreach ($groupedData as $sku => $group) {
        $info = $group['info'];
        $items = $group['items'];
        $name = $info[0];

        echo "Processing Bundle: $name ($sku)...\n";

        $categoryName = $info[1];
        $unitName = $info[3];
        $desc = $info[5];
        $sellPrice = floatval(str_replace(['Rp', ' '], '', $info[6] ?? 0));
        $salesAccountCode = $info[10] ?? null;

        // 1. Create/Update Bundle Product
        $category = $categoryName ? Category::firstOrCreate(['name' => $categoryName]) : null;
        $unit = $unitName ? Unit::firstOrCreate(['name' => $unitName]) : null;
        $salesAccount = $salesAccountCode ? Account::where('code', $salesAccountCode)->first() : null;

        $product = Product::where('sku', $sku)->first();
        if (!$product) {
            $product = new Product();
            $product->sku = $sku;
        }

        $product->name = $name;
        $product->type = 'bundle';
        $product->category_id = $category?->id;
        $product->unit_id = $unit?->id;
        $product->description = $desc;
        $product->sell_price = $sellPrice;
        $product->sales_account_id = $salesAccount?->id;
        // Bundle usually doesn't track inventory itself, but tracks components
        // But system logic might require it to be true/false. Assuming standard.
        $product->is_active = true;

        try {
            $product->save();
        } catch (\Exception $e) {
            echo "  ERROR Saving Product: " . $e->getMessage() . "\n";
            continue;
        }

        // 2. Clear existing items
        try {
            DB::table('product_bundles')->where('product_id', $product->id)->delete(); // Wait, check model.
            // Model: protected $fillable = ['bundle_id', 'product_id', 'quantity'];
            // bundle() relation: belongsTo(Product::class, 'bundle_id');
            // So product_bundles table likely has: id, bundle_id (parent), product_id (child), quantity
            // The file ProductBundle.php confirms: bundle_id is parent.
            // BUT wait, standard pivot naming is usually product_bundle if alphabetical?
            // Let's check schema or assume based on Model keys. 
            // Model says: bundle_id and product_id.
            // If I am the bundle, my ID is in 'bundle_id' column.

            // Let's verify this assumption.
            // If ProductBundle model has 'bundle_id' and 'product_id', then:
            // $this->hasMany(ProductBundle::class, 'bundle_id') in Product model?
            // Checking Product.php: 
            // public function productBundles(): HasMany { return $this->hasMany(ProductBundle::class, 'product_id'); }
            // Wait, Product.php says: return $this->hasMany(ProductBundle::class, 'product_id');
            // This suggests 'product_id' is the FOREIGN KEY on product_bundles table pointing to the Product (Parent).
            // Let's check ProductBundle.php again. 
            // public function bundle() { return $this->belongsTo(Product::class, 'bundle_id'); }
            // public function product() { return $this->belongsTo(Product::class, 'product_id'); }

            // This is confusing without table schema.
            // Let's assume standard Filament repeater behavior which usually links 'parent_id' to relation.
            // If Product hasMany ProductBundle, and ProductBundle has 'product_id' and 'item_id'?
            // Let's check DB schema quickly first.
        } catch (\Exception $e) {
        }
    }
} catch (\Exception $e) {
}

// ABORTING SCRIPT GENERATION TO CHECK SCHEMA
echo "STOP";
