<?php

namespace App\Filament\Resources\PurchaseInvoiceResource\Pages;

use App\Filament\Resources\PurchaseInvoiceResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\PurchaseOrder;

class CreatePurchaseInvoice extends CreateRecord
{
    protected static string $resource = PurchaseInvoiceResource::class;

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


    protected static ?string $title = 'Buat Tagihan Pembelian';

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            url('/admin/pembelian-page') => 'Pembelian',
            PurchaseInvoiceResource::getUrl('index') => 'Tagihan Pembelian',
            '#' => 'Buat Tagihan',
        ];
    }

    public function getMaxContentWidth(): string|null
    {
        return 'full';
    }

    protected function fillForm(): void
    {
        $this->callHook('beforeFill');

        $poId = request()->query('purchase_order_id');
        if ($poId) {
            $this->populateFromPurchaseOrder($poId);
        } else {
            $this->form->fill();
        }

        $this->callHook('afterFill');
    }

    public function mount(): void
    {
        parent::mount();
    }

    public function populateFromPurchaseOrder($poId): void
    {
        $po = PurchaseOrder::with(['supplier', 'warehouse', 'shippingMethod', 'items.product', 'items.unit', 'deliveries.shippingMethod'])->find($poId);

        if (!$po) {
            $this->form->fill();
            return;
        }

        // Generate a new PI number if needed
        $piNumber = null;
        $last = \App\Models\PurchaseInvoice::latest('id')->first();
        if ($last && preg_match('/PI\/(\d{5})/', $last->number, $matches)) {
            $piNumber = 'PI/' . str_pad(intval($matches[1]) + 1, 5, '0', STR_PAD_LEFT);
        } else {
            $piNumber = 'PI/00001';
        }

        // Items preparation
        $items = [];

        foreach ($po->items as $item) {
            $items[] = [
                'product_id' => (int) $item->product_id,
                'product_name' => $item->product?->name ?? '-',
                'description' => (string) $item->description,
                'quantity' => (float) $item->quantity,
                'unit_id' => (int) $item->unit_id,
                'unit_name' => $item->unit?->name ?? '-',
                'unit_price' => (float) $item->unit_price,
                'discount_percent' => (float) ($item->discount_percent ?? 0),
                'tax_id' => $item->tax_id,
                'tax_name' => $item->tax_name ?? 'Bebas Pajak',
                'tax_amount' => (float) $item->tax_amount,
                'total_price' => (float) $item->total_price,
            ];
        }

        $delivery = $po->deliveries->last();

        // Build the final data array
        $data = [
            'purchase_order_id' => (int) $poId,
            'purchase_order_number' => $po->number,
            'is_locked_po' => true,
            'number' => $piNumber,
            'supplier_id' => (int) $po->supplier_id,
            'supplier_name' => $po->supplier?->name,
            'warehouse_id' => (int) $po->warehouse_id,
            'warehouse_name' => $po->warehouse?->name,
            'payment_term_id' => (int) $po->payment_term_id,
            'reference' => (string) $po->reference,
            'tax_inclusive' => (bool) $po->tax_inclusive,
            'date' => $po->date instanceof \Carbon\Carbon ? $po->date->toDateString() : (is_string($po->date) ? $po->date : now()->toDateString()),
            'due_date' => $po->due_date instanceof \Carbon\Carbon ? $po->due_date->toDateString() : (is_string($po->due_date) ? $po->due_date : now()->addDays(30)->toDateString()),
            'shipping_date' => ($delivery?->date ?? $po->shipping_date) instanceof \Carbon\Carbon ? ($delivery?->date ?? $po->shipping_date)->toDateString() : ($delivery?->date ?? $po->shipping_date),
            'shipping_method_id' => $delivery?->shipping_method_id ?? $po->shipping_method_id,
            'shipping_method_name' => $delivery?->shippingMethod?->name ?? $po->shippingMethod?->name,
            'tracking_number' => $delivery?->tracking_number ?? $po->tracking_number,
            'tags' => $po->tags->pluck('id')->toArray(),
            'items' => $items,
            'sub_total' => $po->sub_total,
            'has_discount' => $po->discount_amount > 0,
            'discount_amount' => $po->discount_amount,
            'has_shipping' => $po->shipping_cost > 0,
            'shipping_cost' => $po->shipping_cost,
            'has_other_cost' => $po->other_cost > 0,
            'other_cost' => $po->other_cost,
            'has_withholding' => ($po->withholding_amount ?? 0) > 0,
            'withholding_amount' => $po->withholding_amount ?? 0,
            'has_down_payment' => $po->down_payment > 0,
            'down_payment' => $po->down_payment,
            'tax_amount' => $po->tax_amount,
            'total_amount' => $po->total_amount,
            'balance_due' => $po->balance_due,
            'status' => 'posted',
            'notes' => (string) $po->notes,
        ];

        // Fill the form state
        $this->form->fill($data);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
