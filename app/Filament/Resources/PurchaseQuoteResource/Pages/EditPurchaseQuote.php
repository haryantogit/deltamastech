<?php

namespace App\Filament\Resources\PurchaseQuoteResource\Pages;

use App\Filament\Resources\PurchaseQuoteResource;
use Filament\Actions;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditPurchaseQuote extends EditRecord
{
    protected static string $resource = PurchaseQuoteResource::class;

    protected static ?string $title = 'Edit Penawaran Pembelian';

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            url('/admin/pembelian-page') => 'Pembelian',
            PurchaseQuoteResource::getUrl('index') => 'Penawaran Pembelian',
            '#' => 'Edit Penawaran',
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    public function getMaxContentWidth(): string|null
    {
        return 'full';
    }
}
