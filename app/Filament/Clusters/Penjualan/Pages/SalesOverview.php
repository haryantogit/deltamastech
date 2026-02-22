<?php

namespace App\Filament\Clusters\Penjualan\Pages;

use App\Filament\Clusters\Penjualan;
use Filament\Pages\Page;

class SalesOverview extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-pie';

    protected string $view = 'filament.clusters.penjualan.pages.sales-overview';

    protected static ?string $cluster = Penjualan::class;

    protected static ?int $navigationSort = 1;

    protected static string|null $navigationLabel = 'Overview';
}
