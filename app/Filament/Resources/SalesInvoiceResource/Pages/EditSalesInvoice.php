<?php

namespace App\Filament\Resources\SalesInvoiceResource\Pages;

use App\Filament\Resources\SalesInvoiceResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions;

class EditSalesInvoice extends EditRecord
{
    protected static string $resource = SalesInvoiceResource::class;

    protected static ?string $title = 'Edit Tagihan Penjualan';

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
            '#' => 'Edit Tagihan',
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\Action::make('kembali')
                ->label('Kembali')
                ->color('gray')
                ->icon('heroicon-o-arrow-left')
                ->url(static::getResource()::getUrl('index')),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord();

        // Pre-populate computed total fields from the database so they
        // display correctly when the edit form first opens, even before
        // the user changes anything (updateTotals only runs on interaction).
        $data['sub_total'] = $record->sub_total ?? 0;
        $data['total_amount'] = $record->total_amount ?? 0;
        $data['balance_due'] = $record->balance_due ?? 0;
        $data['discount_total'] = $record->discount_total ?? 0;
        $data['shipping_cost'] = $record->shipping_cost ?? 0;
        $data['other_cost'] = $record->other_cost ?? 0;
        $data['down_payment'] = $record->down_payment ?? 0;

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
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
