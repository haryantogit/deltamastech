<?php

use App\Models\Contact;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Warehouse;
use App\Models\Unit;
use App\Models\Tax;
use App\Models\ShippingMethod;
use App\Models\Tag;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$csvPath = 'D:\Program Receh\kledo\data-baru\pesanan-pembelian_19-Feb-2026_halaman-1.csv';

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

$rawHeader = fgetcsv($tempFile);
$header = array_map(function ($h) {
    return trim($h, " \t\n\r\0\x0B\"");
}, $rawHeader);

// Flexible mapping
$mapping = [];
foreach ($header as $index => $name) {
    if (str_contains($name, 'Nama Kontak'))
        $mapping['contact_name'] = $name;
    if (str_contains($name, 'Perusahaan'))
        $mapping['company'] = $name;
    if (str_contains($name, 'Email'))
        $mapping['email'] = $name;
    if (str_contains($name, 'Alamat'))
        $mapping['address'] = $name;
    if (str_contains($name, 'Provinsi'))
        $mapping['province'] = $name;
    if (str_contains($name, 'Kota'))
        $mapping['city'] = $name;
    if (str_contains($name, 'Nomor Telepon'))
        $mapping['phone'] = $name;
    if (str_contains($name, 'Nomor Penawaran'))
        $mapping['reference'] = $name;
    if (str_contains($name, 'Nomor Pesanan'))
        $mapping['order_number'] = $name;
    if (str_contains($name, 'Tanggal Transaksi'))
        $mapping['date'] = $name;
    if (str_contains($name, 'Tanggal Jatuh Tempo'))
        $mapping['due_date'] = $name;
    if (str_contains($name, 'Nama / Kode Gudang'))
        $mapping['warehouse'] = $name;
    if (str_contains($name, 'Catatan'))
        $mapping['notes'] = $name;
    if (str_contains($name, 'Tanggal Pengiriman'))
        $mapping['shipping_date'] = $name;
    if (str_contains($name, 'Ekspedisi'))
        $mapping['shipping_method'] = $name;
    if (str_contains($name, 'Nomor Resi'))
        $mapping['tracking_number'] = $name;
    if (str_contains($name, 'Termasuk Pajak'))
        $mapping['tax_inclusive'] = $name;
    if (str_contains($name, 'Nama Produk'))
        $mapping['product_name'] = $name;
    if (str_contains($name, 'Kode Produk'))
        $mapping['sku'] = $name;
    if (str_contains($name, 'Deskripsi Produk'))
        $mapping['item_desc'] = $name;
    if (str_contains($name, 'Jumlah Produk'))
        $mapping['qty'] = $name;
    if (str_contains($name, 'Satuan Produk'))
        $mapping['unit'] = $name;
    if (str_contains($name, 'Diskon Produk') && !str_contains($name, 'Persen'))
        $mapping['item_discount'] = $name;
    if (str_contains($name, 'Harga Produk'))
        $mapping['price'] = $name;
    if (str_contains($name, 'Pajak Produk'))
        $mapping['tax_name'] = $name;
    if (str_contains($name, 'Diskon Tambahan') && !str_contains($name, 'Persen'))
        $mapping['extra_discount'] = $name;
    if (str_contains($name, 'Biaya Pengiriman'))
        $mapping['shipping_cost'] = $name;
    if (str_contains($name, 'Jumlah Uang Muka') && !str_contains($name, 'Persen'))
        $mapping['down_payment'] = $name;
    if (str_contains($name, 'Tag'))
        $mapping['tags'] = $name;
    if (str_contains($name, 'Total'))
        $mapping['total'] = $name;
}
echo "Mapping: " . json_encode($mapping) . "\n";
if (!isset($mapping['product_name']) || !isset($mapping['sku'])) {
    die("Error: Could not map product_name or sku columns.\n");
}

$rows = [];
while (($data = fgetcsv($tempFile)) !== false) {
    if (count($header) !== count($data))
        continue;
    $rows[] = array_combine($header, $data);
}
fclose($tempFile);

$orderGroups = [];
foreach ($rows as $row) {
    $orderNumber = trim($row[$mapping['order_number']]);
    if (!isset($orderGroups[$orderNumber])) {
        $orderGroups[$orderNumber] = [
            'header' => $row,
            'items' => []
        ];
    }
    $orderGroups[$orderNumber]['items'][] = $row;
}

echo "Clearing existing test data...\n";
DB::statement('SET FOREIGN_KEY_CHECKS=0;');
PurchaseOrderItem::truncate();
PurchaseOrder::truncate();
DB::statement('SET FOREIGN_KEY_CHECKS=1;');

DB::beginTransaction();
try {
    foreach ($orderGroups as $orderNumber => $group) {
        $headerRow = $group['header'];

        // Contact
        $contactName = trim($headerRow[$mapping['contact_name']] ?? '');
        if (empty($contactName)) {
            echo "Warning: Skipping order $orderNumber due to missing contact name.\n";
            continue;
        }
        $contact = Contact::firstOrCreate(
            ['name' => $contactName],
            ['type' => 'supplier']
        );

        // Warehouse
        $warehouseName = trim($headerRow[$mapping['warehouse']]);
        if ($warehouseName === 'Unassigned')
            $warehouseName = 'Gudang Utama';
        $warehouse = Warehouse::firstOrCreate(['name' => $warehouseName], ['code' => strtoupper(substr($warehouseName, 0, 3))]);

        // Shipping Method
        $shippingMethodId = null;
        if (!empty($headerRow[$mapping['shipping_method']])) {
            $sm = ShippingMethod::firstOrCreate(['name' => trim($headerRow[$mapping['shipping_method']])]);
            $shippingMethodId = $sm->id;
        }

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

        $date = $parseDate($headerRow[$mapping['date']]) ?: date('Y-m-d');
        $dueDate = $parseDate($headerRow[$mapping['due_date']]) ?: $date;

        $po = PurchaseOrder::create([
            'number' => $orderNumber,
            'date' => $date,
            'due_date' => $dueDate,
            'payment_term_id' => (function () use ($date, $dueDate) {
                if ($date === $dueDate) {
                    return \App\Models\PaymentTerm::where('days', 0)->first()->id;
                }
                $days = (int) abs(round((strtotime($dueDate) - strtotime($date)) / 86400));
                // Find term with closest days or exact match
                $term = \App\Models\PaymentTerm::where('days', $days)->first();
                if (!$term) {
                    // Fallback to closest term or creating new one? 
                    // Let's create if not exists for exact days to be safe, or just pick nearest?
                    // Safe approach: create new term for specific days if it doesn't exist.
                    $term = \App\Models\PaymentTerm::firstOrCreate(
                        ['days' => $days],
                        ['name' => "Net $days"]
                    );
                }
                return $term->id;
            })(),
            'supplier_id' => $contact->id,
            'warehouse_id' => $warehouse->id,
            'shipping_date' => $parseDate($headerRow[$mapping['shipping_date']]),
            'shipping_method_id' => $shippingMethodId,
            'tracking_number' => trim($headerRow[$mapping['tracking_number']] ?? ''),
            'status' => 'draft',
            'tax_inclusive' => str_contains(strtolower($headerRow[$mapping['tax_inclusive']]), 'ya'),
            'discount_amount' => (float) preg_replace('/[^\d.]/', '', str_replace(',', '.', $headerRow[$mapping['extra_discount']] ?: 0)),
            'shipping_cost' => (float) preg_replace('/[^\d.]/', '', str_replace(',', '.', $headerRow[$mapping['shipping_cost']] ?: 0)),
            'down_payment' => (float) preg_replace('/[^\d.]/', '', str_replace(',', '.', $headerRow[$mapping['down_payment']] ?: 0)),
            'notes' => $headerRow[$mapping['notes']] ?? null,
            'reference' => !empty($headerRow[$mapping['reference']]) ? $headerRow[$mapping['reference']] : ($headerRow[$mapping['notes']] ?? null),
            'total_amount' => (float) preg_replace('/[^\d.]/', '', str_replace(',', '.', $headerRow[$mapping['total']] ?: 0)),
        ]);

        $subTotal = 0;
        $taxTotal = 0;

        foreach ($group['items'] as $itemRow) {
            $sku = trim($itemRow[$mapping['sku']] ?? '');
            $pName = trim($itemRow[$mapping['product_name']] ?? '');

            if (empty($sku) || empty($pName)) {
                echo "Warning: Skipping blank item in order $orderNumber\n";
                continue;
            }

            $qty = (float) preg_replace('/[^\d.]/', '', str_replace(',', '.', $itemRow[$mapping['qty']] ?: 0));
            $price = (float) preg_replace('/[^\d.]/', '', str_replace(',', '.', $itemRow[$mapping['price']] ?: 0));
            $discount = (float) preg_replace('/[^\d.]/', '', str_replace(',', '.', $itemRow[$mapping['item_discount']] ?: 0));

            $unit = Unit::firstOrCreate(['name' => trim($itemRow[$mapping['unit']] ?: 'Pcs')]);

            $product = Product::firstOrCreate(
                ['sku' => $sku],
                [
                    'name' => $pName,
                    'type' => 'standard',
                    'can_be_purchased' => true,
                    'buy_price' => $price,
                    'cost_of_goods' => $price, // Explicitly set to avoid observer issues
                    'unit_id' => $unit->id
                ]
            );

            $taxId = null;
            $taxRate = 0;
            if (!empty($itemRow[$mapping['tax_name']])) {
                $taxName = trim($itemRow[$mapping['tax_name']]);
                $tax = Tax::where('name', $taxName)->first();
                if (!$tax)
                    $tax = Tax::create(['name' => $taxName, 'rate' => ($taxName === 'PPN' ? 11 : 0)]);
                $taxId = $tax->id;
                $taxRate = $tax->rate;
            }

            $itemBase = ($qty * $price) - $discount;
            $itemTax = 0;
            if ($taxId)
                $itemTax = $itemBase * ($taxRate / 100);

            PurchaseOrderItem::create([
                'purchase_order_id' => $po->id,
                'product_id' => $product->id,
                'description' => $itemRow[$mapping['item_desc']],
                'unit_id' => $unit->id,
                'quantity' => $qty,
                'unit_price' => $price,
                'discount_percent' => ($qty * $price) > 0 ? ($discount / ($qty * $price)) * 100 : 0,
                'tax_id' => $taxId,
                'tax_name' => $itemRow[$mapping['tax_name']] ?: null,
                'tax_amount' => $itemTax,
                'total_price' => $itemBase + $itemTax,
            ]);

            $subTotal += $itemBase;
            $taxTotal += $itemTax;
        }

        $po->update(['sub_total' => $subTotal, 'tax_amount' => $taxTotal]);
        echo "Imported: $orderNumber (ID: {$po->id})\n";
    }

    DB::commit();
    echo "SUCCESS: Imported " . count($orderGroups) . " orders.\n";
} catch (\Exception $e) {
    DB::rollBack();
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
