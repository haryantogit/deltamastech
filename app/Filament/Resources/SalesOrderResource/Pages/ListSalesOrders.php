<?php

namespace App\Filament\Resources\SalesOrderResource\Pages;

use App\Filament\Resources\SalesOrderResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use App\Models\SalesOrder;

class ListSalesOrders extends ListRecords
{
    protected static string $resource = SalesOrderResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Semua')
                ->badge(SalesOrder::count()),
            'draft' => Tab::make('Draft')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'draft'))
                ->badge(SalesOrder::where('status', 'draft')->count()),
            'ordered' => Tab::make('Dipesan')
                ->modifyQueryUsing(fn(Builder $query) => $query->whereIn('status', ['confirmed', 'ordered']))
                ->badge(SalesOrder::whereIn('status', ['confirmed', 'ordered'])->count()),
            'partial_shipping' => Tab::make('Terkirim Sebagian')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'partial_shipped'))
                ->badge(SalesOrder::where('status', 'partial_shipped')->count()),
            'delivered' => Tab::make('Terkirim')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'delivered'))
                ->badge(SalesOrder::where('status', 'delivered')->count()),
            'billed' => Tab::make('Tagihan Dikirim')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'billed'))
                ->badge(SalesOrder::where('status', 'billed')->count()),
            'closed' => Tab::make('Selesai')
                ->modifyQueryUsing(fn(Builder $query) => $query->whereIn('status', ['completed', 'paid', 'selesai', 'terbit']))
                ->badge(SalesOrder::whereIn('status', ['completed', 'paid', 'selesai', 'terbit'])->count()),
            'overdue' => Tab::make('Jatuh tempo')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('due_date', '<', now())->whereIn('status', ['confirmed', 'ordered', 'billed', 'partial_shipped']))
                ->badge(SalesOrder::where('due_date', '<', now())->whereIn('status', ['confirmed', 'ordered', 'billed', 'partial_shipped'])->count()),
            'recurring' => Tab::make('Transaksi Berulang')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('id', '<', 0)) // Placeholder
                ->badge(0),
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
            CreateAction::make()
                ->label('Tambah Pesanan')
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
        return view('filament.resources.sales-order-resource.pages.list-sales-orders-footer');
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin/penjualan-page') => 'Penjualan',
            '#' => 'Pesanan Penjualan',
        ];
    }
}
