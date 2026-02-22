<?php

namespace App\Filament\Resources\PurchaseQuoteResource\Pages;

use App\Filament\Resources\PurchaseQuoteResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPurchaseQuotes extends ListRecords
{
    protected static string $resource = PurchaseQuoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Penawaran')
                ->color('primary'),
            Actions\Action::make('print')
                ->label('Cetak')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->extraAttributes(['onclick' => 'window.print(); return false;']),
            Actions\Action::make('back')
                ->label('Kembali')
                ->url(url('/admin'))
                ->color('gray'),
        ];
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            url('/admin/pembelian-page') => 'Pembelian',
            '#' => 'Penawaran Pembelian',
        ];
    }

    public function getFooter(): ?\Illuminate\Contracts\View\View
    {
        return view('filament.resources.purchase-quote-resource.pages.list-purchase-quotes-footer');
    }
}
