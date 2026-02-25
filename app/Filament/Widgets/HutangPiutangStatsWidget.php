<?php

namespace App\Filament\Widgets;

use App\Models\Contact;
use App\Models\Debt;
use App\Models\Receivable;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class HutangPiutangStatsWidget extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        // Exclude debts/receivables created from purchase/sales invoices
        $purchaseInvoiceNumbers = \App\Models\PurchaseInvoice::pluck('number')->toArray();
        $salesInvoiceNumbers = \App\Models\SalesInvoice::pluck('invoice_number')->toArray();

        // Calculate total hutang (outstanding) - only manual entries
        $totalHutang = 0;
        Debt::whereNotIn('reference', $purchaseInvoiceNumbers)
            ->chunk(100, function ($debts) use (&$totalHutang) {
                foreach ($debts as $debt) {
                    $paid = $debt->payments()->sum('amount');
                    $outstanding = (float) $debt->total_amount - (float) $paid;
                    if ($outstanding > 0.01)
                        $totalHutang += $outstanding;
                }
            });

        // Calculate total piutang (outstanding) - only manual entries
        $totalPiutang = 0;
        Receivable::whereNotIn('invoice_number', $salesInvoiceNumbers)
            ->chunk(100, function ($receivables) use (&$totalPiutang) {
                foreach ($receivables as $receivable) {
                    $paid = $receivable->payments()->sum('amount');
                    $outstanding = (float) $receivable->total_amount - (float) $paid;
                    if ($outstanding > 0.01)
                        $totalPiutang += $outstanding;
                }
            });

        $net = $totalPiutang - $totalHutang;

        // Count contacts with outstanding - only manual entries
        $kontakHutang = Debt::whereNotIn('reference', $purchaseInvoiceNumbers)
            ->select('supplier_id')
            ->groupBy('supplier_id')
            ->pluck('supplier_id')
            ->count();

        $kontakPiutang = Receivable::whereNotIn('invoice_number', $salesInvoiceNumbers)
            ->select('contact_id')
            ->groupBy('contact_id')
            ->pluck('contact_id')
            ->count();

        $fmt = fn($v) => number_format($v, 0, ',', '.');

        return [
            Stat::make('Hutang', $fmt($totalHutang))
                ->description($kontakHutang . ' kontak')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger')
                ->chart([5, 6, 7, 8, 7, 6, 5]),

            Stat::make('Piutang', $fmt($totalPiutang))
                ->description($kontakPiutang . ' kontak')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success')
                ->chart([3, 5, 4, 6, 5, 7, 6]),

            Stat::make('Net Hutang Piutang', $fmt($net))
                ->description($net >= 0 ? 'Piutang lebih besar' : 'Hutang lebih besar')
                ->descriptionIcon($net >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($net >= 0 ? 'success' : 'danger')
                ->chart([4, 5, 6, 3, 7, 5, 6]),

            Stat::make('Kontak', ($kontakHutang + $kontakPiutang))
                ->description($kontakHutang . ' hutang, ' . $kontakPiutang . ' piutang')
                ->descriptionIcon('heroicon-m-users')
                ->color('info')
                ->chart([6, 7, 8, 7, 9, 8, 10]),
        ];
    }
}
