<?php

namespace App\Filament\Resources\Warehouses\Pages;

use App\Filament\Resources\Warehouses\WarehouseResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWarehouses extends ListRecords
{
    protected static string $resource = WarehouseResource::class;

    public bool $showCharts = false;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('toggleCharts')
                ->label(fn() => $this->showCharts ? 'Sembunyikan Grafik' : 'Tampilkan Grafik')
                ->icon(fn() => $this->showCharts ? 'heroicon-m-eye-slash' : 'heroicon-m-eye')
                ->color('gray')
                ->action(fn() => $this->showCharts = !$this->showCharts),
            \Filament\Actions\Action::make('kembali')
                ->label('Kembali')
                ->color('gray')
                ->icon('heroicon-o-arrow-left')
                ->url(url('/admin/inventori-page')),
            CreateAction::make()
                ->label('Tambah Gudang')
                ->color('primary')
                ->modal(),
        ];
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            url('/admin/inventori-page') => 'Inventori',
            '#' => 'Gudang',
        ];
    }

    protected function getHeaderWidgets(): array
    {
        if (!$this->showCharts) {
            return [
                \App\Filament\Resources\Warehouses\Widgets\WarehouseStatsOverview::class,
            ];
        }

        return [
            \App\Filament\Resources\Warehouses\Widgets\WarehouseStatsOverview::class,
            \App\Filament\Resources\Warehouses\Widgets\WarehouseStockChart::class,
            \App\Filament\Resources\Warehouses\Widgets\WarehouseValueChart::class,
        ];
    }
}
