<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\ActionGroup;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Product;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    public bool $showStats = false;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Actions\CreateAction::make('create_standard')
                    ->label('Produk Standard (Stok Fisik)')
                    ->icon('heroicon-m-cube')
                    ->url(fn() => ProductResource::getUrl('create', ['type' => 'standard'])),
                Actions\CreateAction::make('create_variant')
                    ->label('Produk Varian')
                    ->icon('heroicon-m-squares-2x2')
                    ->url(fn() => ProductResource::getUrl('create', ['type' => 'variant'])),
                Actions\CreateAction::make('create_manufacturing')
                    ->label('Manufaktur (Produksi)')
                    ->icon('heroicon-m-cog-6-tooth')
                    ->url(fn() => ProductResource::getUrl('create', ['type' => 'manufacturing'])),
                Actions\CreateAction::make('create_bundle')
                    ->label('Paket / Bundle')
                    ->icon('heroicon-m-archive-box')
                    ->url(fn() => ProductResource::getUrl('create', ['type' => 'bundle'])),
            ])
                ->label('Tambah Produk')
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->button(),

            Actions\Action::make('kembali')
                ->label('Kembali')
                ->color('gray')
                ->icon('heroicon-o-arrow-left')
                ->url(url('/admin/inventori-page')),

            Actions\Action::make('print')
                ->label('Cetak')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->action(fn() => null)
                ->extraAttributes(['onclick' => 'window.print(); return false;']),

            Actions\Action::make('toggleStats')
                ->label(fn() => $this->showStats ? 'Sembunyikan Statistik' : 'Tampilkan Statistik')
                ->icon(fn() => $this->showStats ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                ->color('gray')
                ->action(fn() => $this->showStats = !$this->showStats),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Semua')
                ->badge(fn() => Product::visibleInProductList()->count()),
            'standard' => Tab::make('Standard')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('type', 'standard'))
                ->badge(fn() => Product::visibleInProductList()->where('type', 'standard')->count()),
            'service' => Tab::make('Jasa')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('type', 'service'))
                ->badge(fn() => Product::visibleInProductList()->where('type', 'service')->count()),
            'manufacturing' => Tab::make('Manufaktur')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('type', 'manufacturing'))
                ->badge(fn() => Product::visibleInProductList()->where('type', 'manufacturing')->count()),
            'bundle' => Tab::make('Paket')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('type', 'bundle'))
                ->badge(fn() => Product::visibleInProductList()->where('type', 'bundle')->count()),
            'variant' => Tab::make('Varian')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('type', 'variant'))
                ->badge(fn() => Product::visibleInProductList()->where('type', 'variant')->count()),
            'fixed_asset' => Tab::make('Aset Tetap')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('type', 'fixed_asset'))
                ->badge(fn() => Product::visibleInProductList()->where('type', 'fixed_asset')->count()),
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

    protected function getHeaderWidgets(): array
    {
        if (!$this->showStats) {
            return [];
        }

        return [
            ProductResource\Widgets\ProductListStatsOverview::class,
        ];
    }

    public function getFooter(): ?\Illuminate\Contracts\View\View
    {
        return view('filament.resources.product-resource.pages.list-products-footer');
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            url('/admin/inventori-page') => 'Inventori',
            '#' => 'Produk',
        ];
    }
}
