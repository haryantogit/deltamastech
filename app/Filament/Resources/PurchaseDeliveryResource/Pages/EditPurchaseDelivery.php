<?php

namespace App\Filament\Resources\PurchaseDeliveryResource\Pages;

use App\Filament\Resources\PurchaseDeliveryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPurchaseDelivery extends EditRecord
{
    protected static string $resource = PurchaseDeliveryResource::class;

    protected static ?string $title = 'Edit Pengiriman Pembelian';

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin/pembelian-page') => 'Pembelian',
            PurchaseDeliveryResource::getUrl('index') => 'Pengiriman Pembelian',
            '#' => 'Edit Pengiriman',
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

}
