<?php
namespace App\Filament\Imports;

use App\Models\SalesDeliveryItem;
use App\Models\SalesDelivery;
use App\Models\SalesOrder;
use App\Models\Product;
use App\Models\Contact;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Carbon\Carbon;

class SalesDeliveryImporter extends Importer
{
    protected static ?string $model = SalesDeliveryItem::class;

    public static function getColumns(): array
    {
        // Mapping sesuai CSV 'pengiriman_29-Jan-2026.csv' (Tanpa Bintang)
        return [
            ImportColumn::make('deliveryNum')
                ->label('Nomor Pengiriman')
                ->requiredMapping()
                ->rules(['required']),
            ImportColumn::make('orderNum')
                ->label('Nomor Pesanan')
                ->requiredMapping(),
            ImportColumn::make('dateRaw')
                ->label('Tanggal Pengiriman')
                ->requiredMapping(),
            ImportColumn::make('sku')
                ->label('Kode Produk (SKU)')
                ->requiredMapping(),
            ImportColumn::make('qty')
                ->label('Kuantitas')
                ->numeric(),
            ImportColumn::make('courier')
                ->label('Ekspedisi'),
            ImportColumn::make('resi')
                ->label('No. Resi'),
            ImportColumn::make('contactName')
                ->label('Nama Kontak'),
        ];
    }

    public function resolveRecord(): ?SalesDeliveryItem
    {
        $row = $this->data;
        // 1. Validasi
        $deliveryNum = $row['deliveryNum'] ?? null;
        $sku = $row['sku'] ?? null;
        if (!$deliveryNum || !$sku)
            return null;

        // 2. Cari Pesanan Induk (Sales Order)
        $orderNum = $row['orderNum'] ?? '';
        $salesOrder = SalesOrder::where('number', $orderNum)->first();

        // 3. Cari Produk
        $product = Product::where('sku', $sku)->first();
        if (!$product)
            return null;

        // 4. Parse Tanggal
        try {
            $date = isset($row['dateRaw']) ? Carbon::createFromFormat('d/m/Y', $row['dateRaw']) : now();
        } catch (\Exception $e) {
            $date = now();
        }

        // 5. UPDATE OR CREATE Parent (Sales Delivery)
        // Ini kunci perbaikannya: Kita paksa update sales_order_id
        $delivery = SalesDelivery::updateOrCreate(
            ['number' => $deliveryNum],
            [
                'date' => $date,
                // Sambungkan ke Sales Order ID (Jika ketemu)
                'sales_order_id' => $salesOrder?->id,
                // Ambil Customer dari Sales Order, atau cari dari Nama Kontak
                'customer_id' => $salesOrder?->customer_id ?? Contact::firstOrCreate(['name' => $row['contactName'] ?? 'Unknown Customer'], ['type' => 'customer'])->id,
                'status' => 'shipped',
                'courier' => $row['courier'] ?? null,
                'tracking_number' => $row['resi'] ?? null,
                'reference' => $row['resi'] ?? null,
            ]
        );

        // 6. Return Item (Cegah Duplikat Item)
        // Cek apakah item ini sudah ada di pengiriman tersebut?
        return SalesDeliveryItem::firstOrNew([
            'sales_delivery_id' => $delivery->id,
            'product_id' => $product->id,
        ])->fill([
                    'quantity' => floatval($row['qty'] ?? 0),
                ]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        return 'Import Pengiriman selesai. Data berhasil diperbarui.';
    }
}
