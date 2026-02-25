<?php

namespace App\Filament\Resources\OutletResource\Pages;

use App\Filament\Resources\OutletResource;
use Filament\Resources\Pages\CreateRecord;

class CreateOutlet extends CreateRecord
{
    protected static string $resource = OutletResource::class;

    protected static ?string $title = 'Tambah Outlet';

    public function getMaxContentWidth(): string
    {
        return 'full';
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin/pos-page') => 'POS',
            OutletResource::getUrl('index') => 'Outlet',
            'Tambah',
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
