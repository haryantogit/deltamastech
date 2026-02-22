<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class FaqPage extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-question-mark-circle';

    protected string $view = 'filament.pages.coming-soon';

    public string $feature = 'FAQ';

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            'FAQ',
        ];
    }

    public function getMaxContentWidth(): string
    {
        return 'full';
    }

    protected static ?int $navigationSort = 99;

    protected static string|null $navigationLabel = 'FAQ';

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
