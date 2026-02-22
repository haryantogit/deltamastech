<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class PenjualanPage extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shopping-cart';

    protected string $view = 'filament.pages.penjualan-page';

    protected static ?string $title = 'Halaman Penjualan';

    public function getMaxContentWidth(): string
    {
        return 'full';
    }

    protected static ?int $navigationSort = 2;

    protected static string|null $navigationLabel = 'Penjualan';

    protected static string|\UnitEnum|null $navigationGroup = null;

    public static function getNavigationItems(): array
    {
        return [
            \Filament\Navigation\NavigationItem::make(static::getNavigationLabel())
                ->group(static::getNavigationGroup())
                ->icon(static::getNavigationIcon())
                ->isActiveWhen(fn() => request()->routeIs([
                    static::getRouteName(),
                    'filament.admin.resources.sales-quotations.sales-quotation.*',
                    'filament.admin.resources.sales-quotations.*',
                    'filament.admin.resources.sales-orders.*',
                    'filament.admin.resources.sales-deliveries.*',
                    'filament.admin.resources.sales-invoices.*',
                    'filament.admin.resources.sales-returns.*',
                    'filament.admin.pages.sales-overview',
                    'filament.admin.resources.pelanggans.*',
                ]))
                ->sort(static::getNavigationSort())
                ->url(static::getNavigationUrl()),
        ];
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            'Penjualan',
        ];
    }
}
