<?php

namespace App\Filament\Resources\SalesQuotations\Pages;

use App\Filament\Resources\SalesQuotations\SalesQuotationResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSalesQuotation extends CreateRecord
{
    protected static string $resource = SalesQuotationResource::class;

    protected static ?string $title = 'Buat Penawaran Penjualan';

    public function getMaxContentWidth(): string|null
    {
        return 'full';
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            url('/admin/penjualan-page') => 'Penjualan',
            SalesQuotationResource::getUrl('index') => 'Penawaran Penjualan',
            '#' => 'Buat Penawaran',
        ];
    }
}
