<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Navigation\NavigationItem;

class ProduksiPage extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog';

    protected string $view = 'filament.pages.produksi-page';

    protected static ?string $title = 'Halaman Produksi';

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            'Produksi',
        ];
    }

    public function getMaxContentWidth(): string
    {
        return 'full';
    }

    protected static bool $shouldRegisterNavigation = true;

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

    public static function getNavigationItem(): NavigationItem
    {
        return NavigationItem::make(static::getNavigationLabel())
            ->group(static::getNavigationGroup())
            ->icon(static::getNavigationIcon())
            ->activeWhen(
                fn() =>
                request()->routeIs('filament.admin.pages.produksi-page') ||
                request()->is('admin/production-orders*') ||
                request()->is('admin/production-report*')
            )
            ->sort(static::getNavigationSort())
            ->url(static::getNavigationUrl());
    }

    protected static ?int $navigationSort = 7;

    protected static string|null $navigationLabel = 'Produksi';

    protected static string|\UnitEnum|null $navigationGroup = null;
}
