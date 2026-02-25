<?php

namespace App\Filament\Resources\HutangResource\Pages;

use App\Filament\Resources\HutangResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions;

class ViewHutang extends ViewRecord
{
    protected static string $resource = HutangResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('kembali')
                ->label('Kembali')
                ->color('gray')
                ->outlined()
                ->size('sm')
                ->icon('heroicon-o-arrow-left')
                ->url(fn() => HutangResource::getUrl('index')),
            Actions\Action::make('print')
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
            HutangResource::getUrl('index') => 'Hutang',
            '#' => 'Lihat Hutang',
        ];
    }
}
