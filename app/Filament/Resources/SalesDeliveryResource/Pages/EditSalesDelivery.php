<?php

namespace App\Filament\Resources\SalesDeliveryResource\Pages;

use App\Filament\Resources\SalesDeliveryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSalesDelivery extends EditRecord
{
    protected static string $resource = SalesDeliveryResource::class;

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


    protected static ?string $title = 'Edit Pengiriman Penjualan';

    public function getMaxContentWidth(): string|null
    {
        return 'full';
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin/penjualan-page') => 'Penjualan',
            SalesDeliveryResource::getUrl('index') => 'Pengiriman Penjualan',
            '#' => 'Edit Pengiriman',
        ];
    }
}
