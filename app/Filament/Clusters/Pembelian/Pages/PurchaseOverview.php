<?php

namespace App\Filament\Clusters\Pembelian\Pages;

use App\Filament\Clusters\Pembelian;
use Filament\Pages\Page;

class PurchaseOverview extends Page
{
    protected static string|null $navigationLabel = 'Overview';

    protected static ?int $navigationSort = 1;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-pie';

    protected string $view = 'filament.clusters.pembelian.pages.purchase-overview';

    protected static ?string $cluster = Pembelian::class;
}
