<?php

namespace App\Filament\Resources\PurchaseDeliveryResource\Pages;

use App\Filament\Resources\PurchaseDeliveryResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use App\Models\PurchaseOrder;
use App\Models\PurchaseDelivery;

class CreatePurchaseDelivery extends CreateRecord
{
    protected static string $resource = PurchaseDeliveryResource::class;

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


    protected static ?string $title = 'Buat Pengiriman Pembelian';

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin/pembelian-page') => 'Pembelian',
            PurchaseDeliveryResource::getUrl('index') => 'Pengiriman Pembelian',
            '#' => 'Buat Pengiriman',
        ];
    }

    /**
     * Override fillForm to pre-populate from PO on page load — same pattern as CreatePurchaseInvoice.
     * Data populates instantly without Alpine JS delay.
     */
    protected function fillForm(): void
    {
        $this->callHook('beforeFill');

        $poId = request()->query('purchase_order_id');
        if ($poId) {
            $this->populateFromPurchaseOrder($poId);
        } else {
            $this->form->fill([
                'number' => 'PD/' . str_pad(PurchaseDelivery::count() + 1, 5, '0', STR_PAD_LEFT),
            ]);
        }

        $this->callHook('afterFill');
    }

    public function populateFromPurchaseOrder($poId): void
    {
        if (!$poId) {
            $this->form->fill();
            return;
        }

        $po = PurchaseOrder::with([
            'items.product',
            'items.unit',
            'supplier',
            'warehouse',
            'shippingMethod',
            'tags',
        ])->find($poId);

        if (!$po) {
            $this->form->fill();
            return;
        }

        // Calculate remaining quantities (not yet delivered)
        $po->load('deliveries.items');
        $deliveredQuantities = [];
        foreach ($po->deliveries as $delivery) {
            foreach ($delivery->items as $dItem) {
                $key = $dItem->product_id . '-' . $dItem->unit_id;
                $deliveredQuantities[$key] = ($deliveredQuantities[$key] ?? 0) + $dItem->quantity;
            }
        }

        $items = [];
        foreach ($po->items as $item) {
            $key = $item->product_id . '-' . $item->unit_id;
            $alreadyDelivered = $deliveredQuantities[$key] ?? 0;
            $remaining = max(0, $item->quantity - $alreadyDelivered);

            if ($remaining > 0) {
                $items[] = [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product?->name ?? '',
                    'description' => $item->description,
                    'quantity' => $remaining,
                    'unit_id' => $item->unit_id,
                    'unit_name' => $item->unit?->name ?? '',
                ];
            }
        }

        $data = [
            'number' => 'PD/' . str_pad(PurchaseDelivery::count() + 1, 5, '0', STR_PAD_LEFT),
            'purchase_order_id' => (int) $poId,
            'purchase_order_number' => $po->number,
            'supplier_id' => (int) $po->supplier_id,
            'supplier_name' => $po->supplier?->name ?? '',
            'warehouse_id' => (int) $po->warehouse_id,
            'warehouse_name' => $po->warehouse?->name ?? '',
            'reference' => $po->number,
            'shipping_date' => $po->shipping_date instanceof \Carbon\Carbon
                ? $po->shipping_date->toDateString()
                : now()->toDateString(),
            'tracking_number' => $po->tracking_number ?? '',
            'shipping_cost' => 0,
            'tags' => $po->tags->pluck('id')->toArray(),
            'items' => $items,
        ];

        $this->form->fill($data);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
