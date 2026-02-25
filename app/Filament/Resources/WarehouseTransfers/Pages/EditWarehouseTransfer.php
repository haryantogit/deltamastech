<?php

namespace App\Filament\Resources\WarehouseTransfers\Pages;

use App\Filament\Resources\WarehouseTransfers\WarehouseTransferResource;
use Filament\Resources\Pages\EditRecord;

class EditWarehouseTransfer extends EditRecord
{
    protected static string $resource = WarehouseTransferResource::class;
    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            url('/admin/inventori-page') => 'Inventori',
            WarehouseTransferResource::getUrl('index') => 'Transfer Gudang',
            '#' => 'Edit Transfer Gudang',
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('kembali')
                ->label('Kembali')
                ->color('gray')
                ->icon('heroicon-o-arrow-left')
                ->url(static::getResource()::getUrl('index')),
            \Filament\Actions\DeleteAction::make(),
        ];
    }
}
