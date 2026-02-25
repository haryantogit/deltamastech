<?php

namespace App\Filament\Resources\PurchaseQuoteResource\Pages;

use App\Filament\Resources\PurchaseQuoteResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePurchaseQuote extends CreateRecord
{
    protected static string $resource = PurchaseQuoteResource::class;

    protected static ?string $title = 'Buat Penawaran Pembelian';

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('kembali')
                ->label('Kembali')
                ->color('gray')
                ->icon('heroicon-o-arrow-left')
                ->url(static::getResource()::getUrl('index')),
        ];
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            url('/admin/pembelian-page') => 'Pembelian',
            PurchaseQuoteResource::getUrl('index') => 'Penawaran Pembelian',
            '#' => 'Buat Penawaran',
        ];
    }

    public function getMaxContentWidth(): string|null
    {
        return 'full';
    }
}
