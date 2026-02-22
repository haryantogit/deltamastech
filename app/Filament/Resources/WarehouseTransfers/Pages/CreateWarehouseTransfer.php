<?php

namespace App\Filament\Resources\WarehouseTransfers\Pages;

use App\Filament\Resources\WarehouseTransfers\WarehouseTransferResource;
use Filament\Resources\Pages\CreateRecord;

class CreateWarehouseTransfer extends CreateRecord
{
    protected static string $resource = WarehouseTransferResource::class;
    public function getBreadcrumbs(): array
    {
        return [
            url('/admin/inventori-page') => 'Inventori',
            '#' => 'Buat Transfer Baru',
        ];
    }
}
