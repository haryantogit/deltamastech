<?php

namespace App\Filament\Resources\PurchaseInvoiceResource\Pages;

use App\Filament\Resources\PurchaseInvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPurchaseInvoice extends EditRecord
{
    protected static string $resource = PurchaseInvoiceResource::class;

    protected static ?string $title = 'Edit Tagihan Pembelian';

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin/pembelian-page') => 'Pembelian',
            PurchaseInvoiceResource::getUrl('index') => 'Tagihan Pembelian',
            '#' => 'Edit Tagihan',
        ];
    }

    public function getMaxContentWidth(): string|null
    {
        return 'full';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            \Filament\Actions\Action::make('back')
                ->label('Kembali')
                ->color('gray')
                ->url(static::getResource()::getUrl('index')),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord();

        $data['sub_total'] = $record->sub_total ?? 0;
        $data['total_amount'] = $record->total_amount ?? 0;
        $data['balance_due'] = $record->balance_due ?? 0;
        $data['discount_amount'] = $record->discount_amount ?? 0;
        $data['shipping_cost'] = $record->shipping_cost ?? 0;
        $data['other_cost'] = $record->other_cost ?? 0;
        $data['down_payment'] = $record->down_payment ?? 0;

        return $data;
    }
}
