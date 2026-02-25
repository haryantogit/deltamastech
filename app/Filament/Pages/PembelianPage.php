<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Pages\Page;

class PembelianPage extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-truck';

    protected string $view = 'filament.pages.pembelian-page';

    protected static ?string $title = 'Halaman Pembelian';

    public function getMaxContentWidth(): string
    {
        return 'full';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('kembali')
                ->label('Kembali')
                ->color('gray')
                ->outlined()
                ->size('sm')
                ->icon('heroicon-o-arrow-left')
                ->url(url('/admin')),
        ];
    }

    protected static ?int $navigationSort = 3;

    protected static string|null $navigationLabel = 'Pembelian';

    protected static string|\UnitEnum|null $navigationGroup = null;

    public static function getNavigationItems(): array
    {
        return [
            \Filament\Navigation\NavigationItem::make(static::getNavigationLabel())
                ->group(static::getNavigationGroup())
                ->icon(static::getNavigationIcon())
                ->isActiveWhen(fn() => request()->routeIs([
                    static::getRouteName(),
                    'filament.admin.resources.purchase-quotes.*',
                    'filament.admin.resources.purchase-orders.*',
                    'filament.admin.resources.purchase-deliveries.*',
                    'filament.admin.resources.purchase-invoices.*',
                    'filament.admin.resources.purchase-returns.*',
                    'filament.admin.pages.purchase-overview',
                    'filament.admin.resources.suppliers.*',
                ]))
                ->sort(static::getNavigationSort())
                ->url(static::getNavigationUrl()),
        ];
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            'Pembelian',
        ];
    }
}
