<?php

namespace App\Filament\Resources\SalesQuotations\Pages;

use App\Filament\Resources\SalesQuotations\SalesQuotationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use App\Models\SalesQuotation;

class ListSalesQuotations extends ListRecords
{
    protected static string $resource = SalesQuotationResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Semua')
                ->badge(SalesQuotation::count()),
            'draft' => Tab::make('Draf')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'draft'))
                ->badge(SalesQuotation::where('status', 'draft')->count()),
            'approved' => Tab::make('Disetujui')
                ->modifyQueryUsing(fn(Builder $query) => $query->whereIn('status', ['approved', 'accepted']))
                ->badge(SalesQuotation::whereIn('status', ['approved', 'accepted'])->count()),
            'rejected' => Tab::make('Ditolak')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'rejected'))
                ->badge(SalesQuotation::where('status', 'rejected')->count()),
            'finished' => Tab::make('Selesai')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'finished'))
                ->badge(SalesQuotation::where('status', 'finished')->count()),
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
            \Filament\Actions\CreateAction::make()
                ->label('Tambah Penawaran')
                ->color('primary'),
            \Filament\Actions\Action::make('print')
                ->label('Cetak')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->extraAttributes(['onclick' => 'window.print(); return false;']),
            \Filament\Actions\Action::make('kembali')
                ->label('Kembali')
                ->color('gray')
                ->icon('heroicon-o-arrow-left')
                ->url(url('/admin/penjualan-page')),
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
