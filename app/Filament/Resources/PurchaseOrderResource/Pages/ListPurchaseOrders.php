<?php

namespace App\Filament\Resources\PurchaseOrderResource\Pages;

use App\Filament\Resources\PurchaseOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use App\Models\PurchaseOrder;

class ListPurchaseOrders extends ListRecords
{
    protected static string $resource = PurchaseOrderResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Semua')
                ->badge(PurchaseOrder::count()),
            'draft' => Tab::make('Draft')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'draft'))
                ->badge(PurchaseOrder::where('status', 'draft')->count()),
            'ordered' => Tab::make('Dipesan')
                ->modifyQueryUsing(fn(Builder $query) => $query->whereIn('status', ['approved', 'ordered']))
                ->badge(PurchaseOrder::whereIn('status', ['approved', 'ordered'])->count()),
            'partial_delivery' => Tab::make('Diterima Sebagian')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'partial_received'))
                ->badge(PurchaseOrder::where('status', 'partial_received')->count()),
            'received' => Tab::make('Diterima')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'received'))
                ->badge(PurchaseOrder::where('status', 'received')->count()),
            'billed' => Tab::make('Tagihan Diterima')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'billed'))
                ->badge(PurchaseOrder::where('status', 'billed')->count()),
            'closed' => Tab::make('Selesai')
                ->modifyQueryUsing(fn(Builder $query) => $query->whereIn('status', ['closed', 'paid']))
                ->badge(PurchaseOrder::whereIn('status', ['closed', 'paid'])->count()),
            'overdue' => Tab::make('Jatuh tempo')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('due_date', '<', now())->whereIn('status', ['approved', 'ordered', 'billed', 'partial_received']))
                ->badge(PurchaseOrder::where('due_date', '<', now())->whereIn('status', ['approved', 'ordered', 'billed', 'partial_received'])->count()),
            'recurring' => Tab::make('Transaksi Berulang')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('id', '<', 0)) // Placeholder: Empty result
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
            Actions\CreateAction::make()
                ->label('Tambah Pesanan')
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
            url('/admin/pembelian-page') => 'Pembelian',
            '#' => 'Pesanan Pembelian',
        ];
    }

    public function getFooter(): ?\Illuminate\Contracts\View\View
    {
        return view('filament.resources.purchase-order-resource.pages.list-purchase-orders-footer');
    }
}
