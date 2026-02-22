<?php

namespace App\Filament\Resources\PurchaseOrderResource\Pages;

use App\Filament\Resources\PurchaseOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePurchaseOrder extends CreateRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    protected static ?string $title = 'Buat Pesanan Pembelian';

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin/pembelian-page') => 'Pembelian',
            PurchaseOrderResource::getUrl('index') => 'Pesanan Pembelian',
            '#' => 'Buat Pesanan',
        ];
    }

    public function getMaxContentWidth(): string|null
    {
        return 'full';
    }
}
