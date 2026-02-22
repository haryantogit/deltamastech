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
            CreateAction::make()
                ->label('Tambah Gudang')
                ->color('primary'),
        ];
    }

    public function getBreadcrumbs(): array
    {
        return [
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
