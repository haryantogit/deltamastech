<?php

namespace App\Filament\Resources\WarehouseTransfers\Pages;

use App\Filament\Resources\WarehouseTransfers\WarehouseTransferResource;
use Filament\Resources\Pages\ListRecords;

class ListWarehouseTransfers extends ListRecords
{
    protected static string $resource = WarehouseTransferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make()
                ->label('Tambah Transfer')
                ->color('primary'),
        ];
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin/inventori-page') => 'Inventori',
            '#' => 'Transfer Gudang',
        ];
    }
}
