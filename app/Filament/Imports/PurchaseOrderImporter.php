<?php

namespace App\Filament\Imports;

use App\Models\PurchaseOrderItem;
use App\Models\PurchaseOrder;
use App\Models\Contact;
use App\Models\Product;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Carbon\Carbon;

class PurchaseOrderImporter extends Importer
{
    protected static ?string $model = PurchaseOrderItem::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('orderNumber')
                ->label('*Nomor Pesanan')
                ->requiredMapping()
                ->rules(['required']),
            ImportColumn::make('supplierName')
                ->label('*Nama Kontak')
                ->requiredMapping(),
            ImportColumn::make('sku')
                ->label('*Kode Produk (SKU)')
                ->requiredMapping(),
            ImportColumn::make('dateRaw')
                ->label('*Tanggal Transaksi (dd/mm/yyyy)')
                ->requiredMapping(),
            ImportColumn::make('qty')
                ->label('*Jumlah Produk')
                ->numeric(),
            ImportColumn::make('price')
                ->label('*Harga Produk')
                ->numeric(),
            ImportColumn::make('total')
                ->label('Total'),

            ImportColumn::make('shipping_method')
                ->label('Ekspedisi / Pengiriman Kurir')
                ->rules(['nullable']),

            ImportColumn::make('tags_raw')
                ->label('Tag (Beberapa Tag Dipisah Dengan Koma)')
                ->rules(['nullable']),

            ImportColumn::make('unit')
                ->label('Satuan Produk')
                ->rules(['nullable']),
        ];
    }

    public function resolveRecord(): ?PurchaseOrderItem
    {
        $row = $this->data;

        // 1. Validation
        $orderNumber = $row['orderNumber'] ?? null;
        $sku = $row['sku'] ?? null;

        if (!$orderNumber || !$sku) {
            return null;
        }

        // 2. Find/Create Supplier (Vendor)
        $supplier = Contact::firstOrCreate(
            ['name' => $row['supplierName']],
            ['type' => 'vendor']
        );

        // 3. Find Product
        $product = Product::where('sku', $sku)->first();
        if (!$product) {
            return null;
        }

        // 4. Parse Date
        try {
            // Check if dateRaw is present
            $dateRaw = $row['dateRaw'] ?? null;
            $date = $dateRaw ? Carbon::createFromFormat('d/m/Y', $dateRaw) : now();
        } catch (\Exception $e) {
            $date = now();
        }

        // 5. Update/Create Parent Purchase Order
        // Clean currency formating from Total (e.g. 1.000.000 -> 1000000)
        $cleanTotal = isset($row['total']) ? (float) filter_var($row['total'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) : 0;

        // Handle Shipping Method
        $shippingMethodId = null;
        if (!empty($row['shipping_method'])) {
            $shippingMethodId = \App\Models\ShippingMethod::firstOrCreate(['name' => $row['shipping_method']])->id;
        }

        $purchaseOrder = PurchaseOrder::updateOrCreate(
            ['number' => $orderNumber],
            [
                'date' => $date,
                'supplier_id' => $supplier->id,
                'status' => 'ordered', // Default history status
                'total_amount' => $cleanTotal,
                'shipping_method_id' => $shippingMethodId,
            ]
        );

        // Handle Tags
        if (!empty($row['tags_raw'])) {
            $tagNames = array_map('trim', explode(',', $row['tags_raw']));
            $tagIds = [];
            foreach ($tagNames as $tagName) {
                if ($tagName) {
                    $tagIds[] = \App\Models\Tag::firstOrCreate(['name' => $tagName])->id;
                }
            }
            $purchaseOrder->tags()->syncWithoutDetaching($tagIds);
        }

        // Handle Product Unit
        if (!empty($row['unit']) && blank($product->unit_id)) {
            $unit = \App\Models\Unit::firstOrCreate(['name' => $row['unit']]);
            $product->update(['unit_id' => $unit->id]);
        }

        // 6. Return Item
        return new PurchaseOrderItem([
            'purchase_order_id' => $purchaseOrder->id,
            'product_id' => $product->id,
            'quantity' => floatval($row['qty'] ?? 0),
            'unit_price' => floatval($row['price'] ?? 0),
            'total_price' => floatval($row['qty'] ?? 0) * floatval($row['price'] ?? 0),
        ]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        return 'Import Pesanan Pembelian selesai. ' . number_format($import->successful_rows) . ' baris berhasil diimpor.';
    }
}
