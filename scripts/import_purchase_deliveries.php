<?php

use App\Models\Contact;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseDelivery;
use App\Models\PurchaseDeliveryItem;
use App\Models\Warehouse;
use App\Models\Unit;
use App\Models\ShippingMethod;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$csvPath = 'D:\Program Receh\kledo\data-baru\pengiriman-pembelian_18-Feb-2026_halaman-1.csv';

if (!file_exists($csvPath)) {
    die("File not found: $csvPath\n");
}

$content = file_get_contents($csvPath);
if (strpos($content, "\xEF\xBB\xBF") === 0) {
    $content = substr($content, 3);
}
$tempFile = tmpfile();
fwrite($tempFile, $content);
fseek($tempFile, 0);

$header = fgetcsv($tempFile);
// Trim headers
$header = array_map(function ($h) {
    return trim($h, " \t\n\r\0\x0B\"");
}, $header);

// Flexible mapping
$mapping = [];
foreach ($header as $index => $name) {
    if (str_contains($name, 'Nomor Pengiriman'))
        $mapping['delivery_number'] = $name;
    if (str_contains($name, 'Nomor Pesanan'))
        $mapping['order_number'] = $name;
    if (str_contains($name, 'Tanggal Pengiriman'))
        $mapping['date'] = $name;
    if (str_contains($name, 'Gudang'))
        $mapping['warehouse'] = $name;
    if (str_contains($name, 'Catatan'))
        $mapping['notes'] = $name;
    if (str_contains($name, 'Ekspedisi'))
        $mapping['shipping_method'] = $name;
    if (str_contains($name, 'No. Resi') || str_contains($name, 'Nomor Resi'))
        $mapping['tracking_number'] = $name;
    if (str_contains($name, 'Biaya Pengiriman'))
        $mapping['shipping_cost'] = $name;
    if (str_contains($name, 'Kode Produk'))
        $mapping['sku'] = $name;
    if (str_contains($name, 'Nama Produk'))
        $mapping['product_name'] = $name;
    if (str_contains($name, 'Deskripsi Produk'))
        $mapping['item_desc'] = $name;
    if (str_contains($name, 'Kuantitas'))
        $mapping['qty'] = $name;
    if (str_contains($name, 'Satuan Produk'))
        $mapping['unit'] = $name;
}

if (!isset($mapping['delivery_number']) || !isset($mapping['order_number'])) {
    die("Error: Could not map required columns.\n");
}

$rows = [];
while (($data = fgetcsv($tempFile)) !== false) {
    if (count($header) !== count($data))
        continue;
    $rows[] = array_combine($header, $data);
}
fclose($tempFile);

$groups = [];
foreach ($rows as $row) {
    $num = trim($row[$mapping['delivery_number']]);
    if (empty($num))
        continue;
    if (!isset($groups[$num])) {
        $groups[$num] = [
            'header' => $row,
            'items' => []
        ];
    }
    $groups[$num]['items'][] = $row;
}

echo "Clearing existing delivery data...\n";
DB::statement('SET FOREIGN_KEY_CHECKS=0;');
PurchaseDeliveryItem::truncate();
PurchaseDelivery::truncate();
DB::statement('SET FOREIGN_KEY_CHECKS=1;');

$parseDate = function ($val) {
    if (empty($val) || $val == '-')
        return null;
    $val = trim($val);
    try {
        if (preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $val)) {
            return Carbon::createFromFormat('d/m/Y', $val)->format('Y-m-d');
        }
        return Carbon::parse($val)->format('Y-m-d');
    } catch (\Exception $e) {
        return null;
    }
};

DB::beginTransaction();
try {
    foreach ($groups as $num => $group) {
        $headerRow = $group['header'];
        $orderNumber = trim($headerRow[$mapping['order_number']]);

        $po = PurchaseOrder::where('number', $orderNumber)->first();
        if (!$po) {
            echo "Warning: PO '$orderNumber' not found for Delivery '$num'. Skipping.\n";
            continue;
        }

        // Warehouse (should match PO or from row)
        $warehouseName = trim($headerRow[$mapping['warehouse']]);
        if ($warehouseName === 'Unassigned')
            $warehouseName = 'Gudang Utama';
        $warehouse = Warehouse::firstOrCreate(['name' => $warehouseName], ['code' => strtoupper(substr($warehouseName, 0, 3))]);

        // Shipping Method
        $shippingMethodId = null;
        if (!empty($mapping['shipping_method']) && !empty($headerRow[$mapping['shipping_method']])) {
            $sm = ShippingMethod::firstOrCreate(['name' => trim($headerRow[$mapping['shipping_method']])]);
            $shippingMethodId = $sm->id;
        }

        $date = $parseDate($headerRow[$mapping['date']]) ?: date('Y-m-d');

        $delivery = PurchaseDelivery::create([
            'number' => $num,
            'date' => $date,
            'purchase_order_id' => $po->id,
            'supplier_id' => $po->supplier_id,
            'warehouse_id' => $warehouse->id,
            'status' => 'received', // Correct enum value
            'shipping_date' => $date, // Same as date?
            'shipping_method_id' => $shippingMethodId,
            'tracking_number' => isset($mapping['tracking_number']) ? trim($headerRow[$mapping['tracking_number']]) : null,
            'notes' => isset($mapping['notes']) ? $headerRow[$mapping['notes']] : null,
            'shipping_cost' => isset($mapping['shipping_cost']) ? (float) preg_replace('/[^\d.]/', '', str_replace(',', '.', $headerRow[$mapping['shipping_cost']])) : 0,
            'tax_inclusive' => $po->tax_inclusive, // Fallback to PO setting
        ]);

        foreach ($group['items'] as $itemRow) {
            $sku = trim($itemRow[$mapping['sku']] ?? '');
            $pName = trim($itemRow[$mapping['product_name']] ?? '');

            if (empty($sku) && empty($pName))
                continue;

            $product = null;
            if (!empty($sku)) {
                $product = Product::where('sku', $sku)->first();
            }
            if (!$product && !empty($pName)) {
                $product = Product::where('name', $pName)->first();
            }

            if (!$product) {
                // Try to Create if missing? Better to warn as it should have been in PO import
                // But for safety, create it as standard
                $unitName = 'Pcs';
                if (isset($mapping['unit']) && !empty($itemRow[$mapping['unit']])) {
                    $unitName = trim($itemRow[$mapping['unit']]);
                }
                $unit = Unit::firstOrCreate(['name' => $unitName]);
                $product = Product::create([
                    'sku' => $sku ?: 'GEN-' . uniqid(),
                    'name' => $pName ?: 'Unknown Product',
                    'type' => 'standard',
                    'can_be_purchased' => true,
                    'unit_id' => $unit->id
                ]);
            }

            $unitName = 'Pcs';
            if (isset($mapping['unit']) && !empty($itemRow[$mapping['unit']])) {
                $unitName = trim($itemRow[$mapping['unit']]);
            }
            $unit = Unit::firstOrCreate(['name' => $unitName]);

            $qty = (float) preg_replace('/[^\d.]/', '', str_replace(',', '.', $itemRow[$mapping['qty']] ?: 0));

            PurchaseDeliveryItem::create([
                'purchase_delivery_id' => $delivery->id,
                'product_id' => $product->id,
                'unit_id' => $unit->id,
                'description' => $itemRow[$mapping['item_desc']] ?? $product->name,
                'quantity' => $qty,
            ]);
        }

        // Update PO status to received?
        $po->update(['status' => 'received']); // Simple logic

        echo "Imported Delivery: $num (PO: $orderNumber)\n";
    }

    DB::commit();
    echo "SUCCESS: Imported deliveries.\n";

} catch (\Exception $e) {
    DB::rollBack();
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
