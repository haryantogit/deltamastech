<?php

namespace App\Filament\Pages\Pos\Widgets;

use App\Models\PosOrder;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PosOrderStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $totalOrders = PosOrder::count();
        $totalPending = PosOrder::where('status', 'pending')->count();
        $totalCompleted = PosOrder::where('status', 'completed')->count();
        $totalVoid = PosOrder::where('status', 'void')->count();

        $totalRevenue = PosOrder::where('status', 'completed')->sum('total');

        return [
            Stat::make('Total Pesanan Penjualan', number_format($totalOrders, 0, ',', '.'))
                ->description('Semua pesanan')
                ->descriptionIcon('heroicon-o-clipboard-document-list')
                ->color('primary'),

            Stat::make('Total Belum Diproses', number_format($totalPending, 0, ',', '.'))
                ->description('Menunggu proses')
                ->descriptionIcon('heroicon-o-clock')
                ->color('warning'),

            Stat::make('Total Selesai', number_format($totalCompleted, 0, ',', '.'))
                ->description($this->formatMoneyShort($totalRevenue))
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success'),

            Stat::make('Total Void', number_format($totalVoid, 0, ',', '.'))
                ->description('Dibatalkan')
                ->descriptionIcon('heroicon-o-x-circle')
                ->color('danger'),
        ];
    }

    protected function formatMoneyShort(float $amount): string
    {
        if ($amount >= 1000000000) {
            return 'Rp ' . number_format($amount / 1000000000, 2, ',', '.') . ' M';
        }
        if ($amount >= 1000000) {
            return 'Rp ' . number_format($amount / 1000000, 2, ',', '.') . ' Jt';
        }
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }
}
