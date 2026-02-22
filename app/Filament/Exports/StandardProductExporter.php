<?php

namespace App\Filament\Exports;

use App\Models\Product;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class StandardProductExporter extends Exporter
{
    protected static ?string $model = Product::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('name')
                ->label('*Nama Produk'),

            ExportColumn::make('sku')
                ->label('Kode / SKU Produk'),

            ExportColumn::make('category.name')
                ->label('*Kategori Produk'),

            ExportColumn::make('image')
                ->label('Gambar Produk')
                ->state(function (Product $record): string {
                    $images = $record->image;
                    if (is_array($images) && !empty($images)) {
                        return $images[0];
                    }
                    return '';
                }),

            ExportColumn::make('unit.name')
                ->label('*Unit Produk (Satuan)'),

            ExportColumn::make('description')
                ->label('Deskripsi Produk'),

            ExportColumn::make('buy_price')
                ->label('Harga Pembelian'),

            ExportColumn::make('purchaseAccount.code')
                ->label('Kode Akun Pembelian'),

            ExportColumn::make('purchase_tax_id')
                ->label('Pajak Pembelian'),

            ExportColumn::make('sell_price')
                ->label('Harga Penjualan'),

            // Wholesale logic: Exporting raw array or first item for reference
            ExportColumn::make('wholesale_prices')
                ->label('Harga Grosir (JSON)')
                ->state(function (Product $record) {
                    return json_encode($record->wholesale_prices);
                }),

            ExportColumn::make('salesAccount.code')
                ->label('Kode Akun Penjualan'),

            ExportColumn::make('sales_tax_id')
                ->label('Pajak Penjualan'),

            ExportColumn::make('inventoryAccount.code')
                ->label('Kode Akun Persediaan (Inventory)'),

            ExportColumn::make('min_stock')
                ->label('Stok Minimal'),

            ExportColumn::make('total_stock')
                ->label('Kuantitas')
                ->state(function (Product $record): float {
                    return $record->stocks()->sum('quantity');
                }),

            ExportColumn::make('cost_of_goods')
                ->label('Harga Pokok Penjualan (HPP)'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your standard product export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
