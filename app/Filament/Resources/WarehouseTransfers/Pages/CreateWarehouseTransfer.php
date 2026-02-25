<?php

namespace App\Filament\Resources\WarehouseTransfers\Pages;

use App\Filament\Resources\WarehouseTransfers\WarehouseTransferResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreateWarehouseTransfer extends CreateRecord
{
    protected static string $resource = WarehouseTransferResource::class;
    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            url('/admin/inventori-page') => 'Inventori',
            WarehouseTransferResource::getUrl('index') => 'Transfer Gudang',
            '#' => 'Buat Transfer Gudang',
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('kembali')
                ->label('Kembali')
                ->color('gray')
                ->icon('heroicon-o-arrow-left')
                ->url(fn() => WarehouseTransferResource::getUrl('index')),
        ];
    }
}
