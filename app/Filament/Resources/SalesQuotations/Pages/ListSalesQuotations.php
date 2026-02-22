<?php

namespace App\Filament\Resources\SalesQuotations\Pages;

use App\Filament\Resources\SalesQuotations\SalesQuotationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSalesQuotations extends ListRecords
{
    protected static string $resource = SalesQuotationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make()
                ->label('Tambah Penawaran')
                ->color('primary'),
            \Filament\Actions\Action::make('print')
                ->label('Cetak')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->extraAttributes(['onclick' => 'window.print(); return false;']),
            \Filament\Actions\Action::make('back')
                ->label('Kembali')
                ->url(url('/admin'))
                ->color('gray'),
        ];
    }

    public function getFooter(): ?\Illuminate\Contracts\View\View
    {
        return view('filament.resources.sales-quotation-resource.pages.list-sales-quotations-footer');
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            url('/admin/penjualan-page') => 'Penjualan',
            '#' => 'Penawaran Penjualan',
        ];
    }
}
