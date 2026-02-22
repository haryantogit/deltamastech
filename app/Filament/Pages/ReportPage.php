<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class ReportPage extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected string $view = 'filament.pages.report-page';

    protected static ?string $title = 'Halaman Laporan';

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            'Laporan',
        ];
    }

    public function getMaxContentWidth(): string
    {
        return 'full';
    }

    protected static ?int $navigationSort = 8;

    protected static string|null $navigationLabel = 'Laporan';

    protected static string|\UnitEnum|null $navigationGroup = null;
}
