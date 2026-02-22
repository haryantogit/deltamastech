<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class Pembelian extends Cluster
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-briefcase';

    protected static ?int $navigationSort = 6;
}
