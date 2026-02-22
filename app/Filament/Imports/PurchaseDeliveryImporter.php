<?php

namespace App\Filament\Imports;

use App\Models\Contact;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseDelivery;
use App\Models\PurchaseDeliveryItem;
use Carbon\Carbon;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class PurchaseDeliveryImporter extends Importer
{
    protected static ?string $model = PurchaseDeliveryItem::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('deliveryNum')
                ->label('Nomor Pengiriman')
                ->requiredMapping()
                ->rules(['required']),
            ImportColumn::make('dateRaw')
                ->label('Tanggal Pengiriman')
                ->requiredMapping()
                ->rules(['required']),
            ImportColumn::make('orderNum')
                ->label('Nomor Pesanan')
                ->requiredMapping(),
            ImportColumn::make('supplierName')
                ->label('Nama Kontak')
                ->requiredMapping(),
            ImportColumn::make('sku')
                ->label('Kode Produk (SKU)')
                ->requiredMapping(),
            ImportColumn::make('qty')
                ->label('Kuantitas')
                ->numeric(),
            ImportColumn::make('resi')
                ->label('No. Resi'),
        ];
    }

    public function resolveRecord(): ?PurchaseDeliveryItem
    {
        $row = $this->data;

        $deliveryNum = $row['deliveryNum'] ?? null;
        $sku = $row['sku'] ?? null;

        if (!$deliveryNum || !$sku) {
            return null;
        }

        // 1. Find Supplier
        $supplierName = $row['supplierName'] ?? 'Unknown';
        $supplier = Contact::firstOrCreate(
            ['name' => $supplierName],
            ['type' => 'vendor']
        );

        // 2. Find Product
        $product = Product::where('sku', $sku)->first();
        if (!$product) {
            return null;
        }

        // 3. Find Parent PO
        $orderNum = $row['orderNum'] ?? null;
        $purchaseOrder = null;
        if ($orderNum) {
            $purchaseOrder = PurchaseOrder::where('number', $orderNum)->first();
        }

        // 4. Parse Date
        try {
            $dateRaw = $row['dateRaw'] ?? null;
            $date = $dateRaw ? Carbon::createFromFormat('d/m/Y', $dateRaw) : now();
        } catch (\Exception $e) {
            $date = now();
        }

        // 5. Update/Create Parent Purchase Delivery
        $delivery = PurchaseDelivery::updateOrCreate(
            ['number' => $deliveryNum],
            [
                'date' => $date,
                'supplier_id' => $supplier->id,
                'purchase_order_id' => $purchaseOrder?->id,
                'tracking_number' => $row['resi'] ?? null,
                'reference' => $row['resi'] ?? null,
                'status' => 'received',
            ]
        );

        // 6. Return Item
        $item = PurchaseDeliveryItem::firstOrNew([
            'purchase_delivery_id' => $delivery->id,
            'product_id' => $product->id,
        ]);
        $item->quantity = floatval($row['qty'] ?? 0);

        return $item;
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        return 'Import Pengiriman Pembelian selesai. ' . number_format($import->successful_rows) . ' item diimpor.';
    }
}
