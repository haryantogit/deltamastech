<?php

namespace App\Filament\Resources\PiutangResource\Pages;

use App\Filament\Resources\PiutangResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePiutang extends CreateRecord
{
    protected static string $resource = PiutangResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('back')
                ->label('Kembali')
                ->color('gray')
                ->url($this->getResource()::getUrl('index')),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            url('/admin/kontak-page') => 'Kontak',
            PiutangResource::getUrl('index') => 'Piutang',
            '#' => 'Tambah Piutang',
        ];
    }
}
