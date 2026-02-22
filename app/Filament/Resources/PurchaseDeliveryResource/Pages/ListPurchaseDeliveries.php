<?php

namespace App\Filament\Resources\PurchaseDeliveryResource\Pages;

use App\Filament\Resources\PurchaseDeliveryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPurchaseDeliveries extends ListRecords
{
    protected static string $resource = PurchaseDeliveryResource::class;

    protected function getHeaderActions(): array
    {
        return [
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
            '#' => 'Pengiriman Pembelian',
        ];
    }
    public function getTabs(): array
    {
        return [
            'all' => \Filament\Schemas\Components\Tabs\Tab::make('Semua')
                ->badge(\App\Models\PurchaseDelivery::count()),
            'draft' => \Filament\Schemas\Components\Tabs\Tab::make('Draft')
                ->modifyQueryUsing(fn(\Illuminate\Database\Eloquent\Builder $query) => $query->where('status', 'draft'))
                ->badge(\App\Models\PurchaseDelivery::where('status', 'draft')->count()),
            'pending' => \Filament\Schemas\Components\Tabs\Tab::make('Menunggu')
                ->modifyQueryUsing(fn(\Illuminate\Database\Eloquent\Builder $query) => $query->where('status', 'pending'))
                ->badge(\App\Models\PurchaseDelivery::where('status', 'pending')->count()),
            'received' => \Filament\Schemas\Components\Tabs\Tab::make('Diterima')
                ->modifyQueryUsing(fn(\Illuminate\Database\Eloquent\Builder $query) => $query->where('status', 'received'))
                ->badge(\App\Models\PurchaseDelivery::where('status', 'received')->count()),
            'cancelled' => \Filament\Schemas\Components\Tabs\Tab::make('Dibatalkan')
                ->modifyQueryUsing(fn(\Illuminate\Database\Eloquent\Builder $query) => $query->where('status', 'cancelled'))
                ->badge(\App\Models\PurchaseDelivery::where('status', 'cancelled')->count()),
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

    public function getFooter(): ?\Illuminate\Contracts\View\View
    {
        return view('filament.resources.purchase-delivery-resource.pages.list-purchase-deliveries-footer');
    }
}
