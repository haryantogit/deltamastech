<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class PosPage extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-computer-desktop';

    protected string $view = 'filament.pages.coming-soon';

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

    public static function getNavigationBadge(): ?string
    {
        return 'Coming Soon';
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    protected static string|\UnitEnum|null $navigationGroup = null;
}
