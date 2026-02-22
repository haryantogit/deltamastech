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
            url('/admin/inventori-page') => 'Inventori',
            '#' => 'Edit Transfer',
        ];
    }
}
