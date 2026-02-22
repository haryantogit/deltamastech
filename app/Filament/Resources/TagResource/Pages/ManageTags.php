<?php

namespace App\Filament\Resources\TagResource\Pages;

use App\Filament\Resources\TagResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageTags extends ManageRecords
{
    protected static string $resource = TagResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Tambah Tag')
                ->color('primary'),
        ];
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            \App\Filament\Pages\Pengaturan::getUrl() => 'Pengaturan',
            'Tag',
        ];
    }
}
