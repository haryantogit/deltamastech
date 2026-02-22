<?php

namespace App\Filament\Resources\ContactResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;
use App\Models\SalesInvoice;
use App\Models\PurchaseInvoice;
use App\Models\Debt;
use App\Models\Receivable;

class ContactStatsWidget extends BaseWidget
{
    public ?Model $record = null;

    protected function getStats(): array
    {
        if (!$this->record)
            return [];

        $recordId = $this->record->id;

        // 1. Anda Hutang (Total outstanding Purchase Invoices + Independent Debts)
        $yourDebt = PurchaseInvoice::where('supplier_id', $recordId)
            ->whereIn('status', ['pending', 'partial'])
            ->sum('total_amount');

        $yourDebt += Debt::where('supplier_id', $recordId)
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->withSum('payments', 'amount')
            ->get()
            ->sum(fn($d) => $d->total_amount - ($d->payments_sum_amount ?? 0));

        // 2. Mereka Hutang (Total outstanding Sales Invoices + Independent Receivables)
        $theirDebt = SalesInvoice::where('contact_id', $recordId)
            ->whereIn('status', ['pending', 'partial'])
            ->sum('total_amount');

        $theirDebt += Receivable::where('contact_id', $recordId)
            ->whereIn('status', ['unpaid', 'partial'])
            ->withSum('payments', 'amount')
            ->get()
            ->sum(fn($r) => $r->total_amount - ($r->payments_sum_amount ?? 0));

        // 3. Pembayaran diterima (Paid Sales Invoices + Independent Receivable Payments)
        $incomeReceived = SalesInvoice::where('contact_id', $recordId)
            ->where('status', 'paid')
            ->sum('total_amount');

        $incomeReceived += \App\Models\ReceivablePayment::whereHas('receivable', fn($q) => $q->where('contact_id', $recordId))
            ->sum('amount');

        // 4. Net Hutang Anda
        $netDebt = $yourDebt - $theirDebt;

        return [
            Stat::make('Anda Hutang', 'Rp ' . number_format($yourDebt, 0, ',', '.'))
                ->description('Hutang ke kontak ini')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('warning'),
            Stat::make('Mereka Hutang', 'Rp ' . number_format($theirDebt, 0, ',', '.'))
                ->description('Piutang dari kontak ini')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('danger'),
            Stat::make('Pembayaran diterima', 'Rp ' . number_format($incomeReceived, 0, ',', '.'))
                ->description('Total pendapatan masuk')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
            Stat::make('Net Hutang Anda', 'Rp ' . number_format($netDebt, 0, ',', '.'))
                ->description('Selisih hutang - piutang')
                ->descriptionIcon('heroicon-m-scale')
                ->color('primary'),
        ];
    }
}