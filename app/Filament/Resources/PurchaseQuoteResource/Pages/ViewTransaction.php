<?php

namespace App\Filament\Resources\PurchaseQuoteResource\Pages;

use App\Filament\Resources\PurchaseQuoteResource;
use Filament\Resources\Pages\ViewRecord;

class ViewTransaction extends ViewRecord
{
    protected static string $resource = PurchaseQuoteResource::class;

    protected string $view = 'filament.resources.purchase-quote-resource.pages.view-transaction';

    public function getTitle(): string
    {
        return 'Detil Penawaran';
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            url('/admin/pembelian-page') => 'Pembelian',
            PurchaseQuoteResource::getUrl('index') => 'Penawaran Pembelian',
            '#' => 'Detil Penawaran',
        ];
    }

    public function approve()
    {
        $this->record->update(['status' => 'accepted']);
        // Use refreshFormData to update the UI
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
}
