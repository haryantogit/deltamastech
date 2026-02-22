<?php

namespace App\Filament\Resources\SalesDeliveryResource\Pages;

use App\Filament\Resources\SalesDeliveryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSalesDelivery extends CreateRecord
{
    protected static string $resource = SalesDeliveryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('back')
                ->label('Kembali')
                ->color('gray')
                ->url(static::getResource()::getUrl('index')),
        ];
    }


    protected static ?string $title = 'Buat Pengiriman Penjualan';

    public function getMaxContentWidth(): string|null
    {
        return 'full';
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin/penjualan-page') => 'Penjualan',
            SalesDeliveryResource::getUrl('index') => 'Pengiriman Penjualan',
            '#' => 'Buat Pengiriman',
        ];
    }

    protected function fillForm(): void
    {
        $this->callHook('beforeFill');

        $salesOrderId = request()->query('sales_order_id');
        if ($salesOrderId) {
            $this->populateFromSalesOrder($salesOrderId);
        } else {
            $this->form->fill([
                'number' => \App\Models\SalesDelivery::generateNumber(),
            ]);
        }

        $this->callHook('afterFill');
    }

    protected function populateFromSalesOrder($salesOrderId): void
    {
        $so = \App\Models\SalesOrder::with(['items.product', 'items.unit', 'customer', 'warehouse', 'shippingMethod', 'tags'])
            ->find($salesOrderId);

        if (!$so) {
            $this->form->fill([
                'number' => \App\Models\SalesDelivery::generateNumber(),
            ]);
            return;
        }

        $items = $so->items->map(fn($item) => [
            'product_id' => $item->product_id,
            'product_name' => $item->product?->name ?? '',
            'description' => $item->description,
            'quantity' => $item->quantity,
            'unit_id' => $item->unit_id,
            'unit_name' => $item->unit?->name ?? '',
        ])->toArray();

        $this->form->fill([
            'number' => \App\Models\SalesDelivery::generateNumber(),
            'customer_id' => (int) $so->customer_id,
            'customer_name' => $so->customer?->name ?? '',
            'sales_order_id' => (int) $so->id,
            'sales_order_number' => $so->number,
            'warehouse_id' => (int) $so->warehouse_id,
            'warehouse_name' => $so->warehouse?->name ?? '',
            'reference' => $so->reference ?? $so->number,
            'shipping_method_id' => $so->shipping_method_id,
            'shipping_cost' => 0,
            'tags' => $so->tags->pluck('id')->toArray(),
            'items' => $items,
        ]);
    }
}
