<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class PosPage extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-computer-desktop';

    protected string $view = 'filament.pages.pos-page';

    public string $feature = 'POS';

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            'POS',
        ];
    }

    public function getMaxContentWidth(): string
    {
        return 'full';
    }

    protected static ?int $navigationSort = 100;
    protected static string|null $navigationLabel = 'POS';
    protected static string|\UnitEnum|null $navigationGroup = null;

    public static function getNavigationItem(): \Filament\Navigation\NavigationItem
    {
        return \Filament\Navigation\NavigationItem::make(static::getNavigationLabel())
            ->group(static::getNavigationGroup())
            ->icon(static::getNavigationIcon())
            ->activeWhen(
                fn() =>
                request()->routeIs('filament.admin.pages.pos-page') ||
                request()->is('admin/pos-page*') ||
                request()->is('admin/web-pos-page*') ||
                request()->is('admin/favorite-product-page*') ||
                request()->is('admin/pos/favorite-product-page*') ||
                request()->is('admin/pos-order-page*') ||
                request()->is('admin/pos/pos-order-page*') ||
                request()->is('admin/pos-settings*') ||
                request()->is('admin/pos/pos-settings*') ||
                request()->is('admin/outlets*') ||
                request()->is('admin/cashier-page*')
            )
            ->sort(static::getNavigationSort())
            ->url(static::getNavigationUrl());
    }
}
