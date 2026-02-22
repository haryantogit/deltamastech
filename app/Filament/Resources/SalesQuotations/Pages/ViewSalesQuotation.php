<?php

namespace App\Filament\Resources\SalesQuotations\Pages;

use App\Filament\Resources\SalesQuotations\SalesQuotationResource;
use Filament\Resources\Pages\ViewRecord;

class ViewSalesQuotation extends ViewRecord
{
    protected static string $resource = SalesQuotationResource::class;

    protected string $view = 'filament.resources.sales-quotations.pages.view-transaction';

    public function approve()
    {
        $this->record->update(['status' => 'accepted']);
        $this->refreshFormData(['status']);

        \Filament\Notifications\Notification::make()
            ->title('Penawaran disetujui')
            ->success()
            ->send();
    }

    public function reject()
    {
        $this->record->update(['status' => 'rejected']);
        $this->refreshFormData(['status']);

        \Filament\Notifications\Notification::make()
            ->title('Penawaran ditolak')
            ->danger()
            ->send();
    }

    public function getTitle(): string
    {
        return 'Detil Penawaran';
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            url('/admin/penjualan-page') => 'Penjualan',
            SalesQuotationResource::getUrl('index') => 'Penawaran Penjualan',
            '#' => 'Detil Penawaran',
        ];
    }
}
