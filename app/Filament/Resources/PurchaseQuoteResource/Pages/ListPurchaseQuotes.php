<?php

namespace App\Filament\Resources\PurchaseQuoteResource\Pages;

use App\Filament\Resources\PurchaseQuoteResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use App\Models\PurchaseQuote;

class ListPurchaseQuotes extends ListRecords
{
    protected static string $resource = PurchaseQuoteResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Semua')
                ->badge(PurchaseQuote::count()),
            'draft' => Tab::make('Draf')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'draft'))
                ->badge(PurchaseQuote::where('status', 'draft')->count()),
            'approved' => Tab::make('Disetujui')
                ->modifyQueryUsing(fn(Builder $query) => $query->whereIn('status', ['approved', 'accepted']))
                ->badge(PurchaseQuote::whereIn('status', ['approved', 'accepted'])->count()),
            'rejected' => Tab::make('Ditolak')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'rejected'))
                ->badge(PurchaseQuote::where('status', 'rejected')->count()),
            'finished' => Tab::make('Selesai')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'finished'))
                ->badge(PurchaseQuote::where('status', 'finished')->count()),
        ];
    }

    public function getTabsContentComponent(): \Filament\Schemas\Components\Component
    {
        return \Filament\Schemas\Components\Tabs::make()
            ->livewireProperty('activeTab')
            ->contained(true)
            ->tabs($this->getCachedTabs())
            ->hidden(empty($this->getCachedTabs()));
    }

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
            Actions\Action::make('kembali')
                ->label('Kembali')
                ->color('gray')
                ->icon('heroicon-o-arrow-left')
                ->url(url('/admin/pembelian-page')),
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
