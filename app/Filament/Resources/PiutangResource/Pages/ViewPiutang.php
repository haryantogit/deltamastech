<?php

namespace App\Filament\Resources\PiutangResource\Pages;

use App\Filament\Resources\PiutangResource;
use Filament\Resources\Pages\ViewRecord;

class ViewPiutang extends ViewRecord
{
    protected static string $resource = PiutangResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('back')
                ->label('Kembali')
                ->color('gray')
                ->url($this->getResource()::getUrl('index')),
            \Filament\Actions\Action::make('print')
                ->label('Print')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->action(fn() => null)
                ->extraAttributes([
                    'onclick' => 'window.print(); return false;',
                ]),
        ];
    }
    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            url('/admin/kontak-page') => 'Kontak',
            PiutangResource::getUrl('index') => 'Piutang',
            '#' => 'Lihat Piutang',
        ];
    }
}
