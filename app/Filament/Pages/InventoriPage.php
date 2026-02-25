<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Pages\Page;

class InventoriPage extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-storefront';

    protected string $view = 'filament.pages.inventori-page';

    protected static ?string $title = 'Halaman Inventori';

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            'Inventori',
        ];
    }

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

    protected static ?int $navigationSort = 7;

    protected static string|null $navigationLabel = 'Inventori';

    protected static string|\UnitEnum|null $navigationGroup = null;

    public static function getNavigationItem(): \Filament\Navigation\NavigationItem
    {
        return \Filament\Navigation\NavigationItem::make(static::getNavigationLabel())
            ->group(static::getNavigationGroup())
            ->icon(static::getNavigationIcon())
            ->activeWhen(
                fn() =>
                request()->routeIs('filament.admin.pages.inventori-page') ||
                request()->routeIs('filament.admin.resources.warehouses.*') ||
                request()->routeIs('filament.admin.resources.warehouse-transfers.*') ||
                request()->routeIs('filament.admin.resources.stock-adjustments.*') ||
                request()->routeIs('filament.admin.resources.stock-movements.*')
            )
            ->sort(static::getNavigationSort())
            ->url(static::getNavigationUrl());
    }
}
