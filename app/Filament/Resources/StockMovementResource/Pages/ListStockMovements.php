<?php

namespace App\Filament\Resources\StockMovementResource\Pages;

use App\Filament\Resources\StockMovementResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStockMovements extends ListRecords
{
    protected static string $resource = StockMovementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No Create Action
        ];
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin/inventori-page') => 'Inventori',
            '#' => 'Riwayat Stok',
        ];
    }
}
