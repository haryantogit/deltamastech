<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
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

    protected static bool $shouldRegisterNavigation = true;

    public static function getNavigationItems(): array
    {
        return [
            \Filament\Navigation\NavigationItem::make(static::getNavigationLabel())
                ->group(static::getNavigationGroup())
                ->icon(static::getNavigationIcon())
                ->isActiveWhen(
                    fn() =>
                    in_array(request()->segment(2), [
                        'kontak-page',
                        'contacts',
                        'hutang',
                        'piutang',
                        'umur-hutang',
                        'umur-piutang',
                    ]) || request()->routeIs('filament.admin.resources.piutang.*', 'filament.admin.resources.hutang.*', 'filament.admin.resources.contacts.*')
                )
                ->sort(static::getNavigationSort())
                ->url(static::getNavigationUrl()),
        ];
    }

    protected static ?int $navigationSort = 11;

    protected static string|null $navigationLabel = 'Kontak';

    protected static string|\UnitEnum|null $navigationGroup = null;

}
