<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Navigation\NavigationItem;

class AnggaranPage extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calculator';

    protected string $view = 'filament.pages.anggaran-page';

    protected static ?string $title = 'Anggaran';

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            'Anggaran',
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
                request()->routeIs('filament.admin.pages.anggaran-page') ||
                request()->is('admin/budgets*') ||
                request()->is('admin/laporan/manajemen-anggaran*') ||
                request()->is('admin/laporan/anggaran-laba-rugi*')
            )
            ->sort(static::getNavigationSort())
            ->url(static::getNavigationUrl());
    }

    protected static ?int $navigationSort = 41;

    protected static string|null $navigationLabel = 'Anggaran';

    protected static string|\UnitEnum|null $navigationGroup = null;
}
