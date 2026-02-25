<?php

namespace App\Filament\Resources\SalesInvoiceResource\Pages;

use App\Filament\Resources\SalesInvoiceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSalesInvoice extends CreateRecord
{
    protected static string $resource = SalesInvoiceResource::class;

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


    protected static ?string $title = 'Buat Tagihan Penjualan';

    public function getMaxContentWidth(): string|null
    {
        return 'full';
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            url('/admin/penjualan-page') => 'Penjualan',
            SalesInvoiceResource::getUrl('index') => 'Tagihan Penjualan',
            '#' => 'Buat Tagihan',
        ];
    }

    public function mount(): void
    {
        parent::mount();
    }

    protected function fillForm(): void
    {
        $this->callHook('beforeFill');

        if ($salesOrderId = request()->query('sales_order_id')) {
            $this->populateFromSalesOrder($salesOrderId);
        } else {
            $this->form->fill();
        }

        $this->callHook('afterFill');
    }

    protected function populateFromSalesOrder($salesOrderId): void
    {
        /** @var \App\Models\SalesOrder $so */
        $so = \App\Models\SalesOrder::with(['customer', 'warehouse', 'shippingMethod', 'items.product', 'items.unit', 'deliveries.shippingMethod'])->find($salesOrderId);
        if (!$so) {
            $this->form->fill();
            return;
        }

        $items = $so->items->map(fn($item) => [
            'product_id' => $item->product_id,
            'product_name' => $item->product?->name ?? '-',
            'description' => $item->description,
            'qty' => (float) $item->quantity,
            'unit_id' => $item->unit_id,
            'unit_name' => $item->unit?->name ?? '-',
            'price' => (float) $item->unit_price,
            'discount_percent' => (float) ($item->discount_percent ?? 0),
            'tax_name' => $item->tax_name ?? 'Bebas Pajak',
            'subtotal' => (float) $item->total_price,
        ])->toArray();

        $delivery = $so->deliveries->last();

        $invoiceNumber = null;
        $last = \App\Models\SalesInvoice::latest('id')->first();
        if ($last && preg_match('/INV\/(\d{5})/', $last->invoice_number, $matches)) {
            $invoiceNumber = 'INV/' . str_pad(intval($matches[1]) + 1, 5, '0', STR_PAD_LEFT);
        } else {
            $invoiceNumber = 'INV/00001';
        }

        $newData = [
            'contact_id' => (int) $so->customer_id,
            'contact_name' => $so->customer?->name,
            'sales_order_id' => (int) $so->id,
            'sales_order_number' => $so->number,
            'is_locked_so' => true,
            'warehouse_id' => (int) $so->warehouse_id,
            'warehouse_name' => $so->warehouse?->name,
            'reference' => (string) $so->reference,
            'notes' => (string) $so->notes,
            'tax_inclusive' => (bool) $so->tax_inclusive,
            'payment_term_id' => (int) $so->payment_term_id,
            'transaction_date' => $so->date instanceof \Carbon\Carbon ? $so->date->toDateString() : (is_string($so->date) ? $so->date : now()->toDateString()),
            'due_date' => $so->due_date instanceof \Carbon\Carbon ? $so->due_date->toDateString() : (is_string($so->due_date) ? $so->due_date : now()->addDays(30)->toDateString()),
            'shipping_date' => ($delivery?->date ?? $so->shipping_date) instanceof \Carbon\Carbon ? ($delivery?->date ?? $so->shipping_date)->toDateString() : ($delivery?->date ?? $so->shipping_date),
            'shipping_method_id' => $delivery?->shipping_method_id ?? $so->shipping_method_id,
            'shipping_method_name' => $delivery?->shippingMethod?->name ?? $so->shippingMethod?->name,
            'tracking_number' => $delivery?->tracking_number ?? $so->tracking_number,
            'shipping_cost' => (float) $so->shipping_cost,
            'other_cost' => (float) $so->other_cost,
            'discount_total' => (float) $so->discount_amount,
            'down_payment' => (float) $so->down_payment,
            'withholding_amount' => 0,
            'items' => $items,
            'invoice_number' => $invoiceNumber,
            'sub_total' => (float) $so->sub_total,
            'total_amount' => (float) $so->total_amount,
            'balance_due' => (float) $so->balance_due,
            'total_tax' => (float) $so->total_tax,
        ];

        // Fill the form state
        $this->form->fill($newData);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $items = $data['items'] ?? [];
        $subTotal = collect($items)->sum(fn($item) => (float) ($item['subtotal'] ?? ($item['qty'] * $item['price'])));
        $discountTotal = (float) ($data['discount_total'] ?? 0);
        $shippingCost = (float) ($data['shipping_cost'] ?? 0);
        $otherCost = (float) ($data['other_cost'] ?? 0);
        $downPayment = (float) ($data['down_payment'] ?? 0);

        $totalAmount = $subTotal - $discountTotal + $shippingCost + $otherCost;
        $balanceDue = $totalAmount - $downPayment;

        $data['sub_total'] = $subTotal;
        $data['total_amount'] = $totalAmount;
        $data['balance_due'] = max(0, $balanceDue);

        return $data;
    }
}
