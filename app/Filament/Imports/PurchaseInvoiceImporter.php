<?php

namespace App\Filament\Imports;

use App\Models\Contact;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use Carbon\Carbon;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class PurchaseInvoiceImporter extends Importer
{
    protected static ?string $model = PurchaseInvoiceItem::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('invoice_number')
                ->label('*Nomor Tagihan')
                ->requiredMapping()
                ->fillRecordUsing(fn() => null),

            ImportColumn::make('transaction_date')
                ->label('*Tanggal Transaksi (dd/mm/yyyy)')
                ->requiredMapping()
                ->fillRecordUsing(fn() => null),

            ImportColumn::make('due_date')
                ->label('*Tanggal Jatuh Tempo (dd/mm/yyyy)')
                ->fillRecordUsing(fn() => null),

            ImportColumn::make('supplier_name')
                ->label('*Nama Kontak')
                ->requiredMapping()
                ->fillRecordUsing(fn() => null),

            ImportColumn::make('warehouse_name')
                ->label('*Nama / Kode Gudang')
                ->fillRecordUsing(fn() => null),

            ImportColumn::make('po_ref')
                ->label('Catatan')
                ->fillRecordUsing(fn() => null),

            ImportColumn::make('product_sku')
                ->label('*Kode Produk (SKU)')
                ->requiredMapping()
                ->fillRecordUsing(fn() => null),

            ImportColumn::make('product_name')
                ->label('*Nama Produk')
                ->fillRecordUsing(fn() => null),

            ImportColumn::make('qty')
                ->label('*Jumlah Produk')
                ->requiredMapping()
                ->numeric(),

            ImportColumn::make('price')
                ->label('*Harga Produk')
                ->requiredMapping()
                ->numeric(),

            ImportColumn::make('grand_total')
                ->label('Jumlah Total Tagihan')
                ->fillRecordUsing(fn() => null),

            ImportColumn::make('shipping_method')
                ->label('Ekspedisi / Pengiriman Kurir')
                ->rules(['nullable']),

            ImportColumn::make('tags_raw')
                ->label('Tag (Beberapa Tag Dipisah Dengan Koma)')
                ->rules(['nullable']),

            ImportColumn::make('unit')
                ->label('Satuan Produk')
                ->rules(['nullable']),

            ImportColumn::make('paid_status')
                ->label('*Status Dibayar (Ya / Tidak / Bertahap)')
                ->fillRecordUsing(fn() => null),
        ];
    }

    public function resolveRecord(): ?PurchaseInvoiceItem
    {
        $invoiceNumber = trim($this->data['invoice_number'] ?? '');
        $sku = trim($this->data['product_sku'] ?? '');

        if (!$invoiceNumber || !$sku) {
            return null;
        }

        $supplierName = trim($this->data['supplier_name'] ?? 'Unknown');
        $rawDate = trim($this->data['transaction_date'] ?? '');
        $rawDueDate = trim($this->data['due_date'] ?? '');
        $warehouseName = trim($this->data['warehouse_name'] ?? '');
        $poRef = trim($this->data['po_ref'] ?? '');
        $productName = trim($this->data['product_name'] ?? '');

        $quantity = (float) str_replace(',', '', $this->data['qty'] ?? 0);
        $price = (float) str_replace(',', '', $this->data['price'] ?? 0);
        $grandTotal = (float) str_replace(',', '', $this->data['grand_total'] ?? 0);

        // 1. Handle Dates (Robust parsing)
        $date = $this->parseDate($rawDate);
        $dueDate = $this->parseDate($rawDueDate) ?: $date;

        // 2. Find or Create Supplier
        $supplier = Contact::firstOrCreate(
            ['name' => $supplierName],
            ['type' => 'vendor']
        );

        // 3. Find Product
        $product = Product::whereRaw('LOWER(TRIM(sku)) = ?', [strtolower($sku)])->first();
        if (!$product) {
            \Illuminate\Support\Facades\Log::warning("Purchase Import: Product with SKU '{$sku}' not found.");
            return null;
        }

        // 4. Find Parent PO
        $purchaseOrder = null;
        if ($poRef) {
            $purchaseOrder = PurchaseOrder::where('number', $poRef)
                ->orWhere('notes', $poRef)
                ->first();
        }

        // 5. Handle Warehouse
        $warehouseId = 3; // Default Unassigned
        if ($warehouseName) {
            $warehouse = \App\Models\Warehouse::where('name', 'like', "%{$warehouseName}%")->first();
            if ($warehouse) {
                $warehouseId = $warehouse->id;
            }
        }

        // 6. Map Status
        $paidStatusStr = strtolower($this->data['paid_status'] ?? '');
        $paymentStatus = match ($paidStatusStr) {
            'ya' => 'paid',
            'tidak' => 'unpaid',
            'bertahap' => 'partial',
            default => 'unpaid',
        };
        $status = ($paymentStatus === 'paid') ? 'paid' : 'posted';

        // 7. UpdateOrCreate Parent PurchaseInvoice
        $invoice = PurchaseInvoice::where('number', $invoiceNumber)->first();

        $invoiceData = [
            'number' => $invoiceNumber,
            'date' => $date,
            'due_date' => $dueDate,
            'supplier_id' => $supplier->id,
            'purchase_order_id' => $purchaseOrder?->id,
            'status' => $status,
            'payment_status' => $paymentStatus,
            'total_amount' => $grandTotal,
            'warehouse_id' => $warehouseId,
            'notes' => $poRef,
            'account_id' => 31, // Default to Hutang Usaha (2-10001)
        ];

        if (!$invoice) {
            $invoice = PurchaseInvoice::create($invoiceData);
        } else {
            // Update if total is present and different
            if ($grandTotal > 0 && $invoice->total_amount != $grandTotal) {
                $invoice->update(['total_amount' => $grandTotal]);
            }
            // Ensure warehouse is set if missing
            if (!$invoice->warehouse_id) {
                $invoice->update(['warehouse_id' => $warehouseId]);
            }
        }

        // 8. Handle Shipping Method
        if (!empty($this->data['shipping_method'])) {
            $shippingMethod = \App\Models\ShippingMethod::firstOrCreate(['name' => $this->data['shipping_method']]);
            $invoice->update(['shipping_method_id' => $shippingMethod->id]);
        }

        // 9. Handle Tags
        if (!empty($this->data['tags_raw'])) {
            $tagNames = array_map('trim', explode(',', $this->data['tags_raw']));
            $tagIds = [];
            foreach ($tagNames as $tagName) {
                if ($tagName) {
                    $tagIds[] = \App\Models\Tag::firstOrCreate(['name' => $tagName])->id;
                }
            }
            $invoice->tags()->syncWithoutDetaching($tagIds);
        }

        // 10. Handle Product Unit
        if (!empty($this->data['unit'])) {
            $unit = \App\Models\Unit::firstOrCreate(['name' => $this->data['unit']]);
            if ($product && blank($product->unit_id)) {
                $product->update(['unit_id' => $unit->id]);
            }
        }


        // 11. Check for duplicate item
        $existingItem = PurchaseInvoiceItem::where('purchase_invoice_id', $invoice->id)
            ->where('product_id', $product->id)
            ->where('quantity', $quantity)
            ->where('unit_price', $price)
            ->first();

        $item = $existingItem ?? new PurchaseInvoiceItem();

        if (!$existingItem) {
            $item->purchase_invoice_id = $invoice->id;
            $item->product_id = $product->id;
            $item->quantity = $quantity;
            $item->unit_price = $price;
            $item->total_price = $quantity * $price;
            $item->save(); // Save immediately to get ID
        }

        // 12. Create/Update Stock Movement (Transaction History)
        // We use PurchaseInvoiceItem as reference to handle multiple lines correctly
        if ($item->exists) {
            \App\Models\StockMovement::updateOrCreate(
                [
                    'reference_type' => PurchaseInvoiceItem::class,
                    'reference_id' => $item->id,
                    'type' => 'purchase',
                ],
                [
                    'product_id' => $product->id,
                    'warehouse_id' => $warehouseId,
                    'quantity' => $quantity, // Positive for Purchase
                    'created_at' => $date . ' ' . Carbon::now()->format('H:i:s'), // Set date to invoice date, keep time
                ]
            );
        }

        return $item;
    }

    protected function parseDate($rawDate): ?string
    {
        if (!$rawDate)
            return null;

        if (is_numeric($rawDate)) {
            return Carbon::create(1899, 12, 30)->addDays((int) $rawDate)->format('Y-m-d');
        }

        try {
            if (preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $rawDate)) {
                return Carbon::createFromFormat('d/m/Y', $rawDate)->format('Y-m-d');
            }
            return Carbon::parse($rawDate)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        return 'Import Tagihan Pembelian selesai. ' . number_format($import->successful_rows) . ' item berhasil diimport.';
    }
}
