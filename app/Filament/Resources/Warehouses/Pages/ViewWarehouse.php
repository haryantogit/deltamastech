<?php

namespace App\Filament\Resources\Warehouses\Pages;

use App\Filament\Resources\Warehouses\WarehouseResource;
use Filament\Resources\Pages\ViewRecord;

class ViewWarehouse extends ViewRecord
{
    protected static string $resource = WarehouseResource::class;

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin/inventori-page') => 'Inventori',
            '#' => 'Lihat Gudang',
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('transfer')
                ->label('Transfer Gudang')
                ->icon('heroicon-o-arrows-right-left')
                ->color('primary')
                ->url(fn() => \App\Filament\Resources\WarehouseTransfers\WarehouseTransferResource::getUrl('index')),
            \Filament\Actions\Action::make('adjustment')
                ->label('Penyesuaian Stok')
                ->icon('heroicon-o-adjustments-horizontal')
                ->color('gray')
                ->url(fn() => \App\Filament\Resources\StockAdjustments\StockAdjustmentResource::getUrl('index')),
            \Filament\Actions\Action::make('print')
                ->label('Print')
                ->icon('heroicon-o-printer')
                ->color('gray'),
            \Filament\Actions\Action::make('back')
                ->label('Kembali')
                ->url(WarehouseResource::getUrl('index'))
                ->color('warning'),
        ];
    }

    public function form(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return $schema->components([]);
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Resources\Warehouses\Widgets\WarehouseInfoWidget::class,
        ];
    }

    public function getRelationManagers(): array
    {
        return [
            \App\Filament\Resources\Warehouses\RelationManagers\StocksRelationManager::class,
        ];
    }
}
