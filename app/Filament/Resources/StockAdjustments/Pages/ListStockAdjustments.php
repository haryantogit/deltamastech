<?php

namespace App\Filament\Resources\StockAdjustments\Pages;

use App\Filament\Resources\StockAdjustments\StockAdjustmentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListStockAdjustments extends ListRecords
{
    protected static string $resource = StockAdjustmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make()
                ->label('Tambah Penyesuaian')
                ->color('primary'),
            \Filament\Actions\Action::make('back')
                ->label('Kembali')
                ->url(url('/admin'))
                ->color('gray'),
        ];
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin/inventori-page') => 'Inventori',
            '#' => 'Penyesuaian Stok',
        ];
    }
}
