<?php

namespace App\Filament\Resources\AccountResource\Pages;

use App\Filament\Resources\AccountResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAccounts extends ListRecords
{
    protected static string $resource = AccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('saldo_awal')
                ->label('Saldo Awal')
                ->icon('heroicon-o-cog-6-tooth')
                ->url(AccountResource::getUrl('saldo-awal'))
                ->color('gray')
                ->outlined()
                ->size('sm'),
            Actions\Action::make('tutup_buku')
                ->label('Tutup Buku')
                ->icon('heroicon-o-book-open')
                ->url(\App\Filament\Resources\ClosingResource::getUrl('create'))
                ->color('gray')
                ->outlined()
                ->size('sm'),
            Actions\Action::make('tanggal_penguncian')
                ->label('Tanggal Penguncian')
                ->icon('heroicon-o-lock-closed')
                ->url('#')
                ->color('gray')
                ->outlined()
                ->size('sm'),
            Actions\CreateAction::make()
                ->label('Tambah Akun')
                ->color('primary')
                ->size('sm'),
            Actions\Action::make('kembali')
                ->label('Kembali')
                ->color('gray')
                ->icon('heroicon-o-arrow-left')
                ->url(url('/admin')),
            Actions\Action::make('print')
                ->label('Cetak')
                ->icon('heroicon-m-printer')
                ->color('gray')
                ->outlined()
                ->size('sm')
                ->extraAttributes(['onclick' => 'window.print(); return false;']),
        ];
    }

    public function getFooter(): ?\Illuminate\Contracts\View\View
    {
        return view('filament.resources.account-resource.pages.list-accounts-footer');
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            '#' => 'Akun',
        ];
    }
}
