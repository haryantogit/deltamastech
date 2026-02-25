<?php

namespace App\Filament\Resources\HutangResource\Pages;

use App\Filament\Resources\HutangResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Actions;

class CreateHutang extends CreateRecord
{
    protected static string $resource = HutangResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('kembali')
                ->label('Kembali')
                ->color('gray')
                ->icon('heroicon-o-arrow-left')
                ->url(fn() => HutangResource::getUrl('index')),
        ];
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            url('/admin/kontak-page') => 'Kontak',
            HutangResource::getUrl('index') => 'Hutang',
            '#' => 'Tambah Hutang',
        ];
    }
}
