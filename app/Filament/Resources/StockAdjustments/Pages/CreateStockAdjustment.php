<?php

namespace App\Filament\Resources\StockAdjustments\Pages;

use App\Filament\Resources\StockAdjustments\StockAdjustmentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateStockAdjustment extends CreateRecord
{
    protected static string $resource = StockAdjustmentResource::class;
    public function getBreadcrumbs(): array
    {
        return [
            url('/admin/inventori-page') => 'Inventori',
            '#' => 'Buat Penyesuaian',
        ];
    }
}
