<?php

use App\Models\Contact;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\Warehouse;
use App\Models\Tag;
use App\Models\ShippingMethod;
use App\Models\Account;
use App\Models\Unit;
use App\Models\PaymentTerm;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Convert Excel date (numeric) to Carbon instance
 */
function excelDateToCarbon($value)
{
    if (is_numeric($value)) {
        return Carbon::create(1899, 12, 30)->addDays((int) $value);
    }

    if (empty($value))
        return null;

    try {
        return Carbon::createFromFormat('d/m/Y', $value);
    } catch (\Exception $e) {
        try {
            return Carbon::parse($value);
        } catch (\Exception $e2) {
            return null;
        }
    }
}

function cleanNumber($value)
{
    if (is_string($value)) {
        $value = preg_replace('/[^0-9,.-]/', '', $value);
        if (preg_match('/,([0-9]{2})$/', $value)) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        } else {
            $value = str_replace(',', '', $value);
        }
    }
    return (float) $value;
}

function importSalesInvoices($filePath)
{
    if (!file_exists($filePath)) {
        echo "File not found: $filePath\n";
        return;
    }

    $handle = fopen($filePath, 'r');
    $header = fgetcsv($handle);

    if (!$header) {
        echo "Empty header in $filePath\n";
        return;
    }

    $colMap = array_flip($header);

    $rowCount = 0;
    $invoiceCount = 0;
    $updateCount = 0;
    $itemCount = 0;

    $defaultSalesAccount = Account::where('code', '4-40000')->first() ?: Account::first();

    while (($data = fgetcsv($handle)) !== FALSE) {
        $rowCount++;

        $invoiceNumber = $data[$colMap['*Nomor Tagihan']] ?? null;
        if (empty($invoiceNumber))
            continue;

        $customerName = $data[$colMap['*Nama Kontak']] ?? null;
        $dateRaw = $data[$colMap['*Tanggal Transaksi (dd/mm/yyyy)']] ?? null;
        $dueDateRaw = $data[$colMap['*Tanggal Jatuh Tempo (dd/mm/yyyy)']] ?? null;
        $warehouseName = $data[$colMap['*Nama / Kode Gudang']] ?? 'Unassigned';
        $reference = $data[$colMap['Catatan']] ?? null;
        $notes = $data[$colMap['Pesan']] ?? null;
        $taxInclusiveRaw = $data[$colMap['*Termasuk Pajak (Ya / Tidak)']] ?? 'Tidak';
        $shippingCost = cleanNumber($data[$colMap['Biaya Pengiriman']] ?? 0);
        $discountTotal = cleanNumber($data[$colMap['Jumlah Diskon Tambahan']] ?? 0);
        $totalAmount = cleanNumber($data[$colMap['Jumlah Total Tagihan']] ?? 0);
        $statusPaidRaw = $data[$colMap['*Status Dibayar (Ya / Tidak / Bertahap)']] ?? 'Tidak';
        $statusTextRaw = $data[$colMap['Status']] ?? null;
        $tagsRaw = $data[$colMap['Tag (Beberapa Tag Dipisah Dengan Koma)']] ?? null;

        $shippingDateRaw = $data[$colMap['Tanggal Pengiriman (dd/mm/yyyy)']] ?? null;
        $shippingMethodName = $data[$colMap['Ekspedisi / Pengiriman Kurir']] ?? null;

        $sku = $data[$colMap['*Kode Produk (SKU)']] ?? null;
        $productName = $data[$colMap['*Nama Produk']] ?? null;
        $description = $data[$colMap['Deskripsi Produk']] ?? null;
        $qty = cleanNumber($data[$colMap['*Jumlah Produk']] ?? 0);
        $unitName = $data[$colMap['Satuan Produk']] ?? null;
        $price = cleanNumber($data[$colMap['*Harga Produk']] ?? 0);
        $discountAmount = cleanNumber($data[$colMap['Diskon Produk']] ?? 0);
        $taxName = $data[$colMap['Pajak Produk']] ?? null;
        $subtotal = cleanNumber($data[$colMap['Subtotal Produk']] ?? 0);

        if (empty($sku))
            continue;

        DB::beginTransaction();
        try {
            $customer = Contact::firstOrCreate(
                ['name' => $customerName],
                ['type' => 'customer']
            );

            $warehouse = Warehouse::where('name', $warehouseName)->first();
            if (!$warehouse) {
                $warehouse = Warehouse::create(['name' => $warehouseName]);
            }

            $date = excelDateToCarbon($dateRaw) ?: now();
            $dueDate = excelDateToCarbon($dueDateRaw) ?: $date->copy()->addDays(30);
            $shippingDate = excelDateToCarbon($shippingDateRaw);

            $status = 'unpaid';
            if ($statusPaidRaw === 'Ya' || strtolower($statusTextRaw) === 'lunas') {
                $status = 'paid';
            } elseif ($statusPaidRaw === 'Bertahap' || strtolower($statusTextRaw) === 'sebagian') {
                $status = 'partial';
            }

            $salesInvoice = SalesInvoice::where('invoice_number', $invoiceNumber)->first();
            if (!$salesInvoice) {
                $salesInvoice = SalesInvoice::create([
                    'invoice_number' => $invoiceNumber,
                    'contact_id' => $customer->id,
                    'reference' => $reference,
                    'transaction_date' => $date,
                    'due_date' => $dueDate,
                    'warehouse_id' => $warehouse->id,
                    'notes' => $notes,
                    'shipping_date' => $shippingDate,
                    'shipping_cost' => $shippingCost,
                    'discount_total' => $discountTotal,
                    'total_amount' => $totalAmount,
                    'balance_due' => ($status === 'paid' ? 0 : $totalAmount),
                    'status' => $status,
                ]);
                $invoiceCount++;
            } else {
                $updateData = [];
                if (!empty($reference))
                    $updateData['reference'] = $reference;
                if (!empty($notes))
                    $updateData['notes'] = $notes;

                if (!empty($updateData)) {
                    $salesInvoice->update($updateData);
                    $updateCount++;
                }
            }

            // Sync tags
            if ($tagsRaw) {
                $tagNames = explode(',', $tagsRaw);
                foreach ($tagNames as $tagName) {
                    $tag = Tag::firstOrCreate(['name' => trim($tagName)]);
                    $salesInvoice->tags()->syncWithoutDetaching([$tag->id]);
                }
            }

            $product = Product::where('sku', $sku)->first();
            if (!$product) {
                $product = Product::create([
                    'name' => $productName ?: $sku,
                    'sku' => $sku,
                    'type' => 'storable',
                    'unit_id' => 1,
                    'sale_account_id' => $defaultSalesAccount->id,
                ]);
            }

            $unitId = $product->unit_id;
            if ($unitName) {
                $unit = Unit::firstOrCreate(['name' => $unitName]);
                $unitId = $unit->id;
            }

            // Only create item if not exists (to avoid duplicates on re-run)
            $existingItem = SalesInvoiceItem::where('sales_invoice_id', $salesInvoice->id)
                ->where('product_id', $product->id)
                ->where('qty', $qty)
                ->where('price', $price)
                ->first();

            if (!$existingItem) {
                SalesInvoiceItem::create([
                    'sales_invoice_id' => $salesInvoice->id,
                    'product_id' => $product->id,
                    'unit_id' => $unitId,
                    'account_id' => $product->sale_account_id ?: $defaultSalesAccount->id,
                    'description' => $description,
                    'qty' => $qty,
                    'price' => $price,
                    'discount_amount' => $discountAmount,
                    'tax_name' => $taxName,
                    'subtotal' => $subtotal ?: ($qty * $price) - $discountAmount,
                ]);
                $itemCount++;
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            echo "Error processing row $rowCount ($invoiceNumber) in $filePath: " . $e->getMessage() . "\n";
        }
    }

    fclose($handle);
    echo "Processed $filePath: Created $invoiceCount, Updated $updateCount invoices. Items sync: $itemCount.\n";
}

$files = [
    'd:/Program Receh/kledo/data/tagihan_29-Jan-2026_halaman-1.csv',
    'd:/Program Receh/kledo/data/tagihan_29-Jan-2026_halaman-2.csv',
    'd:/Program Receh/kledo/data/tagihan_29-Jan-2026_halaman-3.csv',
];

foreach ($files as $file) {
    importSalesInvoices($file);
}
