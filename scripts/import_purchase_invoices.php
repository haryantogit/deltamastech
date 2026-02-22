<?php

use App\Models\Contact;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Models\Warehouse;
use App\Models\Unit;
use App\Models\ShippingMethod;
use App\Models\Tax;
use App\Models\Account;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$csvPath = 'D:\Program Receh\kledo\data-baru\tagihan-pembelian_18-Feb-2026_halaman-1.csv';
// $csvPath = 'D:\Program Receh\kledo\data-baru\tagihan-pembelian-sample.csv'; // Use actual path

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
    if (str_contains($name, 'Nomor Tagihan'))
        $mapping['invoice_number'] = $name;
    // if (str_contains($name, 'Nomor Pesanan')) $mapping['order_number'] = $name; // Not in header, inside Catatan
    if (str_contains($name, 'Tanggal Transaksi'))
        $mapping['date'] = $name;
    if (str_contains($name, 'Tanggal Jatuh Tempo'))
        $mapping['due_date'] = $name;
    if (str_contains($name, 'Gudang') || str_contains($name, 'Nama / Kode Gudang'))
        $mapping['warehouse'] = $name;
    if (str_contains($name, 'Catatan'))
        $mapping['notes'] = $name;
    if (str_contains($name, 'Nama Kontak'))
        $mapping['contact_name'] = $name;
    if (str_contains($name, 'Ekspedisi'))
        $mapping['shipping_method'] = $name;
    if (str_contains($name, 'Nomor Resi'))
        $mapping['tracking_number'] = $name;
    if (str_contains($name, 'Termasuk Pajak'))
        $mapping['tax_inclusive'] = $name;
    if (str_contains($name, 'Biaya Pengiriman'))
        $mapping['shipping_cost'] = $name;
    if (str_contains($name, 'Kode Produk'))
        $mapping['sku'] = $name;
    if (str_contains($name, 'Nama Produk'))
        $mapping['product_name'] = $name;
    if (str_contains($name, 'Deskripsi Produk'))
        $mapping['item_desc'] = $name;
    if (str_contains($name, 'Jumlah Produk'))
        $mapping['qty'] = $name;
    if (str_contains($name, 'Satuan Produk'))
        $mapping['unit'] = $name;
    if (str_contains($name, 'Harga Produk'))
        $mapping['price'] = $name;
    if (str_contains($name, 'Diskon Produk') && !str_contains($name, 'Persen'))
        $mapping['discount'] = $name;
    if (str_contains($name, 'Pajak Produk'))
        $mapping['tax_name'] = $name;
    if (str_contains($name, 'Diskon Tambahan') && !str_contains($name, 'Persen'))
        $mapping['extra_discount'] = $name;
    if (str_contains($name, 'Uang Muka') && !str_contains($name, 'Persen'))
        $mapping['down_payment'] = $name;
}

if (!isset($mapping['invoice_number'])) {
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
    $num = trim($row[$mapping['invoice_number']]);
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

echo "Clearing existing invoice data...\n";
DB::statement('SET FOREIGN_KEY_CHECKS=0;');
PurchaseInvoiceItem::truncate();
PurchaseInvoice::truncate();
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

$parseMoney = function ($val) {
    return (float) preg_replace('/[^\d.]/', '', str_replace(',', '.', $val ?: 0));
};

DB::beginTransaction();
try {
    foreach ($groups as $num => $group) {
        $headerRow = $group['header'];

        // Find PO from Notes (Catatan)
        $notes = $headerRow[$mapping['notes']] ?? '';
        $poNumber = null;
        if (preg_match('/PO\/\d+/', $notes, $matches)) {
            $poNumber = $matches[0];
        }

        $po = null;
        if ($poNumber) {
            $po = PurchaseOrder::where('number', $poNumber)->first();
        }

        // Contact
        $contactName = trim($headerRow[$mapping['contact_name']] ?? '');
        $contact = Contact::where('name', $contactName)->first();
        if (!$contact) {
            $contact = Contact::firstOrCreate(['name' => $contactName], ['type' => 'supplier']);
        }

        // Warehouse
        $warehouseName = trim($headerRow[$mapping['warehouse']] ?? '');
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
        $dueDate = $parseDate($headerRow[$mapping['due_date']]) ?: $date;

        // $downPayment = $parseMoney($headerRow[$mapping['down_payment']] ?? 0);
        $downPayment = 0; // Force 0 as per user request to handle payment validation manually

        $status = 'posted';
        $paymentStatus = 'unpaid';

        // Note: We don't have total_amount yet, it is calculated later. 
        // We will update payment_status after calculating total.

        /*
        if ($downPayment == 0 && $po && $po->down_payment > 0) {
            // Logic: Use PO down payment if available and CSV has none
            // Be careful not to over-apply if multiple invoices exist, but for now exact match or simple coverage
            $downPayment = min($po->total_amount, $po->down_payment); // Simplified
        }
        */

        $invoice = PurchaseInvoice::withoutEvents(function () use ($num, $date, $dueDate, $po, $contact, $warehouse, $shippingMethodId, $headerRow, $mapping, $notes, $parseMoney, $downPayment, $status, $paymentStatus) {
            return PurchaseInvoice::create([
                'number' => $num,
                'date' => $date,
                'due_date' => $dueDate,
                'purchase_order_id' => $po ? $po->id : null,
                'supplier_id' => $contact->id,
                'warehouse_id' => $warehouse->id,
                'status' => $status,
                'payment_status' => $paymentStatus,
                'shipping_date' => null, // Simplified
                'shipping_method_id' => $shippingMethodId,
                'tracking_number' => isset($mapping['tracking_number']) ? trim($headerRow[$mapping['tracking_number']]) : null,
                'notes' => $notes,
                'tax_inclusive' => str_contains(strtolower($headerRow[$mapping['tax_inclusive']] ?? ''), 'ya'),
                'shipping_cost' => $parseMoney($headerRow[$mapping['shipping_cost']] ?? 0),
                'discount_amount' => $parseMoney($headerRow[$mapping['extra_discount']] ?? 0),
                'down_payment' => $downPayment,
                'sub_total' => 0, // Will update
                'tax_amount' => 0, // Will update
                'total_amount' => 0, // Will update
                'other_cost' => 0,
                'withholding_amount' => 0,
            ]);
        });

        $subTotal = 0;
        $taxTotal = 0;

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
                    'unit_id' => $unit->id,
                    'buy_price' => $parseMoney($itemRow[$mapping['price']] ?? 0)
                ]);
            }

            $unitName = 'Pcs';
            if (isset($mapping['unit']) && !empty($itemRow[$mapping['unit']])) {
                $unitName = trim($itemRow[$mapping['unit']]);
            }
            $unit = Unit::firstOrCreate(['name' => $unitName]);

            $qty = $parseMoney($itemRow[$mapping['qty']] ?? 0);
            $price = $parseMoney($itemRow[$mapping['price']] ?? 0);
            $discount = $parseMoney($itemRow[$mapping['discount']] ?? 0);

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

            PurchaseInvoiceItem::create([
                'purchase_invoice_id' => $invoice->id,
                'product_id' => $product->id,
                'unit_id' => $unit->id,
                'description' => $itemRow[$mapping['item_desc']] ?? $product->name,
                'quantity' => $qty,
                'unit_price' => $price,
                'discount_percent' => ($qty * $price) > 0 ? ($discount / ($qty * $price)) * 100 : 0,
                'tax_id' => $taxId,
                'tax_name' => $itemRow[$mapping['tax_name']] ?? null,
                'tax_amount' => $itemTax,
                'total_price' => $itemBase + $itemTax,
                'account_id' => $product->purchase_account_id, // Best effort
            ]);

            $subTotal += $itemBase;
            $taxTotal += $itemTax;
        }

        $total = $subTotal + $taxTotal + $invoice->shipping_cost - $invoice->discount_amount;

        PurchaseInvoice::withoutEvents(function () use ($invoice, $subTotal, $taxTotal, $total) {
            $invoice->update([
                'sub_total' => $subTotal,
                'tax_amount' => $taxTotal,
                'total_amount' => $total,
                'status' => 'posted',
                'payment_status' => 'unpaid',
            ]);
        });

        // Manually create Debt
        \App\Models\Debt::updateOrCreate(
            ['reference' => $invoice->number],
            [
                'supplier_id' => $invoice->supplier_id,
                'number' => $invoice->number,
                'date' => $invoice->date,
                'due_date' => $invoice->due_date,
                'total_amount' => $total,
                'notes' => $invoice->notes,
                'payment_status' => $invoice->payment_status ?? 'unpaid',
                'status' => 'posted',
            ]
        );

        echo "Imported Invoice: $num (PO: " . ($poNumber ?: 'None') . ")\n";
    }

    DB::commit();
    echo "SUCCESS: Imported invoices.\n";

} catch (\Exception $e) {
    DB::rollBack();
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
