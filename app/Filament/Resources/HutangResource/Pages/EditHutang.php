<?php

namespace App\Filament\Resources\HutangResource\Pages;

use App\Filament\Resources\HutangResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions;

class EditHutang extends EditRecord
{
    protected static string $resource = HutangResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('Kembali')
                ->color('gray')
                ->url(fn() => HutangResource::getUrl('index')),
            Actions\DeleteAction::make(),
        ];
    }
    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            url('/admin/kontak-page') => 'Kontak',
            HutangResource::getUrl('index') => 'Hutang',
            '#' => 'Edit Hutang',
        ];
    }
}
