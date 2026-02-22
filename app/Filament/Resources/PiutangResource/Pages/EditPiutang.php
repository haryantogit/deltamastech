<?php

namespace App\Filament\Resources\PiutangResource\Pages;

use App\Filament\Resources\PiutangResource;
use Filament\Resources\Pages\EditRecord;

class EditPiutang extends EditRecord
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
    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            url('/admin/kontak-page') => 'Kontak',
            PiutangResource::getUrl('index') => 'Piutang',
            '#' => 'Edit Piutang',
        ];
    }
}
