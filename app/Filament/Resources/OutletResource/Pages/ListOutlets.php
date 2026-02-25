<?php

namespace App\Filament\Resources\OutletResource\Pages;

use App\Filament\Resources\OutletResource;
use App\Models\Outlet;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\CreateAction;

class ListOutlets extends ListRecords
{
    protected static string $resource = OutletResource::class;

    protected string $view = 'filament.resources.outlet-resource.pages.list-outlets';

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin/pos-page') => 'POS',
            'Outlet',
        ];
    }

    protected function getViewData(): array
    {
        return [
            'records' => Outlet::all(),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Tambah')
                ->icon('heroicon-o-plus'),
        ];
    }
}
