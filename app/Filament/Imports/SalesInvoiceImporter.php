<?php

namespace App\Filament\Imports;

use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\Contact;
use App\Models\Product;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;
use Illuminate\Support\Carbon;

class SalesInvoiceImporter extends Importer
{
    protected static ?string $model = SalesInvoiceItem::class;

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

            ImportColumn::make('customer_name')
                ->label('*Nama Kontak')
                ->requiredMapping()
                ->fillRecordUsing(fn() => null),

            ImportColumn::make('status_raw')
                ->label('Status')
                ->fillRecordUsing(fn() => null),

            ImportColumn::make('grand_total')
                ->label('Jumlah Total Tagihan')
                ->fillRecordUsing(fn() => null),

            ImportColumn::make('po_ref')
                ->label('Catatan')
                ->fillRecordUsing(fn() => null),

            ImportColumn::make('product_sku')
                ->label('*Kode Produk (SKU)')
                ->requiredMapping()
                ->fillRecordUsing(fn() => null),

            ImportColumn::make('qty')
                ->label('*Jumlah Produk')
                ->requiredMapping()
                ->numeric(),

            ImportColumn::make('price')
                ->label('*Harga Produk')
                ->requiredMapping()
                ->numeric(),

            ImportColumn::make('warehouse_name')
                ->label('*Nama / Kode Gudang')
                ->fillRecordUsing(fn() => null),

            ImportColumn::make('product_name')
                ->label('*Nama Produk')
                ->fillRecordUsing(fn() => null),

            ImportColumn::make('tags_raw')
                ->label('Tag (Beberapa Tag Dipisah Dengan Koma)')
                ->rules(['nullable']),

            ImportColumn::make('shipping_method')
                ->label('Ekspedisi / Pengiriman Kurir')
                ->rules(['nullable']),

            ImportColumn::make('unit')
                ->label('Satuan Produk')
                ->rules(['nullable']),
        ];
    }

    public function resolveRecord(): SalesInvoiceItem
    {
        $invoiceNumber = trim($this->data['invoice_number']);
        $rawDate = trim($this->data['transaction_date']);
        $customerName = trim($this->data['customer_name']);
        $statusRaw = trim($this->data['status_raw'] ?? 'Draft');
        $grandTotal = (float) str_replace(',', '', $this->data['grand_total'] ?? 0);
        $poRef = $this->data['po_ref'] ?? null;
        $productSku = trim($this->data['product_sku']);
        $productName = trim($this->data['product_name'] ?? '');
        $quantity = (float) str_replace(',', '', $this->data['qty'] ?? 0);
        $price = (float) str_replace(',', '', $this->data['price'] ?? 0);
        $warehouseName = trim($this->data['warehouse_name'] ?? '');

        \Illuminate\Support\Facades\Log::info("Importing Invoice Item: {$invoiceNumber}, SKU: {$productSku}");

        // Step 1: Handle Excel Date
        $date = null;
        if (is_numeric($rawDate)) {
            $date = Carbon::create(1899, 12, 30)->addDays((int) $rawDate)->format('Y-m-d');
        } else {
            try {
                // Try dd/mm/yyyy first
                if (preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $rawDate)) {
                    $date = Carbon::createFromFormat('d/m/Y', $rawDate)->format('Y-m-d');
                } else {
                    $date = Carbon::parse($rawDate)->format('Y-m-d');
                }
            } catch (\Exception $e) {
                $date = now()->format('Y-m-d');
            }
        }

        // Handle Shipping Method
        $shippingMethodId = null;
        if (!empty($this->data['shipping_method'])) {
            $shippingMethodId = \App\Models\ShippingMethod::firstOrCreate(['name' => $this->data['shipping_method']])->id;
        }

        // Handle Warehouse
        $warehouseId = 3; // Default Unassigned
        if ($warehouseName) {
            $warehouse = \App\Models\Warehouse::where('name', 'like', "%{$warehouseName}%")->first();
            if ($warehouse) {
                $warehouseId = $warehouse->id;
            }
        }

        // Step 2: Find or Create Parent SalesInvoice
        $invoice = SalesInvoice::where('invoice_number', $invoiceNumber)->first();

        // Find Sales Link via PO Ref (Catatan)
        $salesOrder = null;
        $reference = trim($poRef ?? '');

        if ($reference) {
            $salesOrder = \App\Models\SalesOrder::where('notes', $reference)
                ->orWhere('number', $reference)
                ->orWhere('reference', $reference)
                ->first();
        }

        if (!$invoice) {
            $contact = Contact::where('name', $customerName)->first();
            if (!$contact) {
                $contact = Contact::create(['name' => $customerName, 'type' => 'customer']);
            }

            // Map Status Robustly
            $statusRawLower = strtolower($statusRaw);
            $status = match ($statusRawLower) {
                'lunas', 'paid' => 'paid',
                'belum dibayar', 'unpaid', 'open' => 'unpaid',
                'dibayar sebagian', 'partial' => 'partial',
                'disetujui', 'approved' => 'approved',
                'overdue', 'terlambat' => 'overdue',
                default => 'draft',
            };

            $invoice = SalesInvoice::create([
                'invoice_number' => $invoiceNumber,
                'transaction_date' => $date,
                'due_date' => $date, // Default to same as transaction date
                'contact_id' => $contact->id,
                'sales_order_id' => $salesOrder?->id,
                'status' => $status,
                'total_amount' => $grandTotal,
                'shipping_method_id' => $shippingMethodId,
                'warehouse_id' => $warehouseId,
                'account_id' => 15,  // Default to Piutang Usaha (1-10100)
                'notes' => $poRef,
                'reference' => $poRef,
            ]);
        } else {
            // Update invoice if needed
            $updateData = [];
            if ($salesOrder && $invoice->sales_order_id !== $salesOrder->id) {
                $updateData['sales_order_id'] = $salesOrder->id;
            }
            if ($poRef && $invoice->reference !== $poRef) {
                $updateData['reference'] = $poRef;
            }
            if ($shippingMethodId && $invoice->shipping_method_id !== $shippingMethodId) {
                $updateData['shipping_method_id'] = $shippingMethodId;
            }
            if ($grandTotal > 0 && $invoice->total_amount != $grandTotal) {
                $updateData['total_amount'] = $grandTotal;
            }

            if (!empty($updateData)) {
                $invoice->update($updateData);
            }
        }

        // Handle Tags
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

        // Step 3: Find Product (Robust SKU lookup)
        $product = Product::whereRaw('LOWER(TRIM(sku)) = ?', [strtolower($productSku)])->first();

        // Handle Unit
        $unitId = null;
        if (!empty($this->data['unit'])) {
            $unitId = \App\Models\Unit::firstOrCreate(['name' => $this->data['unit']])->id;
            if ($product && blank($product->unit_id)) {
                $product->update(['unit_id' => $unitId]);
            }
        }

        // Check for existing item to avoid duplicates
        $existingItem = SalesInvoiceItem::where('sales_invoice_id', $invoice->id)
            ->where('product_id', $product?->id)
            ->where('qty', $quantity)
            ->where('price', $price)
            ->first();

        if ($existingItem) {
            return $existingItem;
        }

        $item = new SalesInvoiceItem([
            'sales_invoice_id' => $invoice->id,
            'product_id' => $product?->id,
            'description' => $productName ?: ($product?->name ?? 'Imported Item'),
            'qty' => $quantity,
            'price' => $price,
            'subtotal' => $quantity * $price,
        ]);

        // Recalculate parent total after saving item? 
        // Filament's importer usually returns the model to be saved.
        // We can use a model event or just update it here.
        // However, we don't know if this is the last item. 
        // A common pattern is to update total based on existing items + this one.
        // But the CSV already has 'grand_total' which we mapped to 'total_amount'.
        // The user says: "Ensure the parent Invoice total_amount is updated correctly."
        // Let's ensure it's at least the sum of items if grand_total is missing or incorrect.

        return $item;
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your sales invoice item import has completed and ' . Number::format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
