<?php

namespace App\Filament\Resources\SalesReturns\Pages;

use App\Filament\Resources\SalesReturns\SalesReturnResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSalesReturn extends EditRecord
{
    protected static string $resource = SalesReturnResource::class;

    protected static ?string $title = 'Ubah Retur Penjualan';

    public function getMaxContentWidth(): string|null
    {
        return 'full';
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            url('/admin/penjualan-page') => 'Penjualan',
            SalesReturnResource::getUrl('index') => 'Retur Penjualan',
            '#' => 'Ubah Retur Penjualan',
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('confirm')
                ->label('Konfirmasi')
                ->icon('heroicon-o-check')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn(\App\Models\SalesReturn $record) => $record->status === 'draft')
                ->action(function (\App\Models\SalesReturn $record) {
                    $record->update(['status' => 'confirmed']);
                    \Filament\Notifications\Notification::make()
                        ->title('Retur Penjualan Dikonfirmasi')
                        ->success()
                        ->send();
                }),

            DeleteAction::make(),
            \Filament\Actions\Action::make('kembali')
                ->label('Kembali')
                ->color('gray')
                ->icon('heroicon-o-arrow-left')
                ->url(static::getResource()::getUrl('index')),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord();
        $data['sub_total'] = $record->sub_total ?? 0;
        $data['tax_amount'] = $record->tax_amount ?? 0;
        $data['total_amount'] = $record->total_amount ?? 0;

        $data['items'] = $record->items->map(function ($item) {
            return [
                'product_id' => $item->product_id,
                'product_name' => $item->product?->name ?? '-',
                'unit_id' => $item->unit_id,
                'unit_name' => $item->unit?->name ?? '-',
                'invoice_qty' => (float) $item->invoice_qty,
                'returnable_qty' => (float) $item->returnable_qty,
                'return_qty' => (float) $item->return_qty,
                'unit_price' => (float) $item->unit_price,
                'discount_percent' => (float) $item->discount_percent,
                'tax_name' => $item->tax_name,
                'tax_amount' => (float) $item->tax_amount,
                'total_price' => (float) $item->total_price,
            ];
        })->toArray();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $items = $data['items'] ?? [];
        $subTotal = 0;
        $taxAmount = 0;

        foreach ($items as $item) {
            $price = (float) ($item['unit_price'] ?? 0);
            $qty = (float) ($item['qty'] ?? 0);
            $discount = (float) ($item['discount_percent'] ?? 0);
            $itemTaxAmount = (float) ($item['tax_amount'] ?? 0);

            $base = $qty * $price;
            $discounted = $base * (1 - ($discount / 100));
            $subTotal += $discounted;
            $taxAmount += $itemTaxAmount;
        }

        $taxInclusive = (bool) ($data['tax_inclusive'] ?? false);
        if ($taxInclusive) {
            $totalAmount = $subTotal;
        } else {
            $totalAmount = $subTotal + $taxAmount;
        }

        $data['sub_total'] = $subTotal;
        $data['tax_amount'] = $taxAmount;
        $data['total_amount'] = $totalAmount;

        return $data;
    }
}
