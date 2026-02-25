<?php

namespace App\Filament\Resources\FixedAssetResource\Pages;

use App\Filament\Resources\FixedAssetResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;

class ListFixedAssets extends ListRecords
{
    protected static string $resource = FixedAssetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('print')
                ->label('Cetak')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->extraAttributes(['onclick' => 'window.print(); return false;']),
            Actions\CreateAction::make()
                ->label('Tambah Aset'),
            Actions\Action::make('kembali')
                ->label('Kembali')
                ->color('gray')
                ->icon('heroicon-o-arrow-left')
                ->url(url('/admin')),
        ];
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            '#' => 'Aset Tetap',
        ];
    }

    public function getFooter(): ?\Illuminate\Contracts\View\View
    {
        return view('filament.resources.fixed-asset-resource.pages.list-fixed-assets-footer');
    }

    public function getTabs(): array
    {
        return [
            'draft' => Tab::make('Draft')
                ->badge(\App\Models\Product::where('is_fixed_asset', true)->where('status', 'draft')->count())
                ->modifyQueryUsing(fn($query) => $query->where('status', 'draft')),
            'registered' => Tab::make('Terdaftar')
                ->badge(\App\Models\Product::where('is_fixed_asset', true)->where('status', 'registered')->count())
                ->modifyQueryUsing(fn($query) => $query->where('status', 'registered')),
            'disposed' => Tab::make('Terjual/Dilepaskan')
                ->badge(\App\Models\Product::where('is_fixed_asset', true)->where('status', 'disposed')->count())
                ->modifyQueryUsing(fn($query) => $query->where('status', 'disposed')),
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 4;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Resources\FixedAssetResource\Widgets\FixedAssetListStatsWidget::class,
        ];
    }

    public function getTabsContentComponent(): \Filament\Schemas\Components\Component
    {
        return parent::getTabsContentComponent()
            ->contained(true);
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'registered';
    }
}
