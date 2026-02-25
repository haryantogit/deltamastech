<?php

namespace App\Filament\Resources\PurchaseReturns\Pages;

use App\Filament\Resources\PurchaseReturns\PurchaseReturnResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPurchaseReturn extends EditRecord
{
    protected static string $resource = PurchaseReturnResource::class;

    protected static ?string $title = 'Edit Retur Pembelian';

    public function getMaxContentWidth(): string|null
    {
        return 'full';
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            url('/admin/pembelian-page') => 'Pembelian',
            PurchaseReturnResource::getUrl('index') => 'Retur Pembelian',
            '#' => 'Edit Retur',
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
                ->visible(fn(\App\Models\PurchaseReturn $record) => $record->status === 'draft')
                ->action(function (\App\Models\PurchaseReturn $record) {
                    $record->update(['status' => 'confirmed']);
                    \Filament\Notifications\Notification::make()
                        ->title('Retur Pembelian Dikonfirmasi')
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
