<?php

namespace App\Filament\Resources\PaymentTermResource\Pages;

use App\Filament\Resources\PaymentTermResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManagePaymentTerms extends ManageRecords
{
    protected static string $resource = PaymentTermResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Tambah Termin')
                ->color('primary'),
        ];
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            \App\Filament\Pages\Pengaturan::getUrl() => 'Pengaturan',
            'Termin Pembayaran',
        ];
    }
}
