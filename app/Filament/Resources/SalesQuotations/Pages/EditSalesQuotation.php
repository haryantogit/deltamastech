<?php

namespace App\Filament\Resources\SalesQuotations\Pages;

use App\Filament\Resources\SalesQuotations\SalesQuotationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSalesQuotation extends EditRecord
{
    protected static string $resource = SalesQuotationResource::class;

    protected static ?string $title = 'Edit Penawaran Penjualan';

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
            '#' => 'Edit Penawaran',
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
