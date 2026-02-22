<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class KontakPage extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected string $view = 'filament.pages.kontak-page';

    protected static ?string $title = 'Halaman Kontak';

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            'Kontak',
        ];
    }

    public function getMaxContentWidth(): string
    {
        return 'full';
    }

    protected static bool $shouldRegisterNavigation = true;

    public static function getNavigationItem(): \Filament\Navigation\NavigationItem
    {
        return \Filament\Navigation\NavigationItem::make(static::getNavigationLabel())
            ->group(static::getNavigationGroup())
            ->icon(static::getNavigationIcon())
            ->activeWhen(
                fn() =>
                request()->routeIs('filament.admin.pages.kontak-page') ||
                request()->routeIs('filament.admin.resources.contacts.*') ||
                request()->routeIs('filament.admin.resources.hutang.*') ||
                request()->routeIs('filament.admin.resources.piutang.*')
            )
            ->sort(static::getNavigationSort())
            ->url(static::getNavigationUrl());
    }

    protected static ?int $navigationSort = 11;

    protected static string|null $navigationLabel = 'Kontak';

    protected static string|\UnitEnum|null $navigationGroup = null;

}
