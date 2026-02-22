<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class Penjualan extends Cluster
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?int $navigationSort = 5;
}
