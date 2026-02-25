<?php

namespace App\Filament\Resources\SalesOrderResource\Pages;

use App\Filament\Resources\SalesOrderResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSalesOrder extends CreateRecord
{
    protected static string $resource = SalesOrderResource::class;

    protected static ?string $title = 'Buat Pesanan Penjualan';

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('kembali')
                ->label('Kembali')
                ->color('gray')
                ->icon('heroicon-o-arrow-left')
                ->url(static::getResource()::getUrl('index')),
        ];
    }

    public function getMaxContentWidth(): string|null
    {
        return 'full';
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin/penjualan-page') => 'Penjualan',
            SalesOrderResource::getUrl('index') => 'Pesanan Penjualan',
            '#' => 'Buat Pesanan',
        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['status'] = 'ordered';
        return $data;
    }

    protected function beforeCreate(): void
    {
        $data = $this->form->getState();
        $warehouseId = $data['warehouse_id'] ?? null;
        $items = $data['items'] ?? [];

        foreach ($items as $item) {
            $productId = $item['product_id'] ?? null;
            $quantity = (float) ($item['quantity'] ?? 0);

            if (!$productId)
                continue;

            $product = \App\Models\Product::find($productId);
            if (!$product)
                continue;

            // Skip stock check for products that don't track inventory
            if (!$product->track_inventory)
                continue;

            $stock = $warehouseId ? (float) $product->getStockForWarehouse($warehouseId) : 0;

            if ($stock <= 0) {
                \Filament\Notifications\Notification::make()
                    ->title('Stok Kosong')
                    ->body("Produk \"{$product->name}\" tidak memiliki stok di gudang yang dipilih.")
                    ->danger()
                    ->persistent()
                    ->send();

                $this->halt();
            }

            if ($quantity > $stock) {
                \Filament\Notifications\Notification::make()
                    ->title('Stok Tidak Cukup')
                    ->body("Produk \"{$product->name}\" hanya tersedia {$stock} unit, tetapi kuantitas yang diminta {$quantity} unit.")
                    ->warning()
                    ->persistent()
                    ->send();

                $this->halt();
            }
        }
    }
}
