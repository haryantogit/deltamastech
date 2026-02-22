<?php

namespace App\Filament\Resources\SalesOrderResource\Pages;

use App\Filament\Resources\SalesOrderResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSalesOrder extends CreateRecord
{
    protected static string $resource = SalesOrderResource::class;

    protected static ?string $title = 'Buat Pesanan Penjualan';

    public function getMaxContentWidth(): string|null
    {
        return 'full';
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin/penjualan-page') => 'Penjualan',
            SalesOrderResource::getUrl('index') => 'Pesanan Penjualan',
            '#' => 'Buat Pesanan',
        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['status'] = 'ordered';
        return $data;
    }
}
