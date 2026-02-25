<?php

namespace App\Filament\Resources\OutletResource\Pages;

use App\Filament\Resources\OutletResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\DeleteAction;

class EditOutlet extends EditRecord
{
    protected static string $resource = OutletResource::class;

    protected static ?string $title = 'Ubah Outlet';

    public function getMaxContentWidth(): string
    {
        return 'full';
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin/pos-page') => 'POS',
            OutletResource::getUrl('index') => 'Outlet',
            'Ubah',
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
