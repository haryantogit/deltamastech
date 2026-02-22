<?php

namespace App\Filament\Resources\FixedAssetResource\Widgets;

use App\Models\Product;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class FixedAssetListStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        // NILAI ASET: Total Net Book Value of ALL registered assets (Stable)
        $nilaiAset = Product::where('is_fixed_asset', true)
            ->where('status', 'registered')
            ->get()
            ->sum(fn($record) => $record->purchase_price - $record->accumulated_depreciation_value);

        // DEPRESIASI ASET: Shown for the current month
        $currentMonth = now()->format('Y-m');
        $depresiasiAset = \App\Models\FixedAssetDepreciation::where('period', $currentMonth)
            ->sum('amount');

        // LABA/RUGI PELEPASAN ASET
        $labaRugiPelepasan = Product::where('is_fixed_asset', true)
            ->where('status', 'disposed')
            ->get()
            ->sum(fn($record) => ($record->disposal_price ?? 0) - ($record->purchase_price - $record->accumulated_depreciation_value));

        $asetBaru = Product::where('is_fixed_asset', true)
            ->where('status', 'registered')
            ->whereYear('purchase_date', now()->year)
            ->count();

        return [
            Stat::make('NILAI ASET', 'Rp ' . number_format($nilaiAset, 0, ',', '.'))
                ->description('Total seluruh aset terdaftar')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('DEPRESIASI ASET', 'Rp ' . number_format($depresiasiAset, 0, ',', '.'))
                ->description('Bulan ini (' . now()->translatedFormat('F') . ')')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('warning'),

            Stat::make('LABA/RUGI PELEPASAN ASET', 'Rp ' . number_format($labaRugiPelepasan, 0, ',', '.'))
                ->description('Total untung/rugi pelepasan')
                ->descriptionIcon('heroicon-m-scale')
                ->color($labaRugiPelepasan >= 0 ? 'success' : 'danger'),

            Stat::make('ASET BARU', $asetBaru)
                ->description('Aset baru tahun ini')
                ->descriptionIcon('heroicon-m-plus-circle')
                ->color('info'),
        ];
    }
}
