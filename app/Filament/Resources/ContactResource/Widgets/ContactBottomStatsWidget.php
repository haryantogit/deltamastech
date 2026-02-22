<?php

namespace App\Filament\Resources\ContactResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;
use App\Models\SalesInvoice;
use App\Models\PurchaseInvoice;
use App\Models\Debt;
use App\Models\Receivable;
use App\Models\DebtPayment;

class ContactBottomStatsWidget extends BaseWidget
{
    public ?Model $record = null;

    protected function getStats(): array
    {
        if (!$this->record)
            return [];

        $recordId = $this->record->id;
        $now = now();

        // 1. Hutang Anda jatuh tempo
        $yourOverdue = PurchaseInvoice::where('supplier_id', $recordId)
            ->whereIn('status', ['pending', 'partial'])
            ->where('due_date', '<', $now)
            ->sum('total_amount');

        $yourOverdue += Debt::where('supplier_id', $recordId)
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->where('due_date', '<', $now)
            ->withSum('payments', 'amount')
            ->get()
            ->sum(fn($d) => $d->total_amount - ($d->payments_sum_amount ?? 0));

        // 2. Hutang mereka jatuh tempo
        $theirOverdue = SalesInvoice::where('contact_id', $recordId)
            ->whereIn('status', ['pending', 'partial'])
            ->where('due_date', '<', $now)
            ->sum('total_amount');

        $theirOverdue += Receivable::where('contact_id', $recordId)
            ->whereIn('status', ['unpaid', 'partial'])
            ->where('due_date', '<', $now)
            ->withSum('payments', 'amount')
            ->get()
            ->sum(fn($r) => $r->total_amount - ($r->payments_sum_amount ?? 0));

        // 3. Pembayaran dikirim (Only actual debt payments made)
        // Note: Don't count paid invoice totals, as that would double-count with debt payments
        $paymentsSent = DebtPayment::whereHas('debt', fn($q) => $q->where('supplier_id', $recordId))
            ->sum('amount');

        // Also add down payments from purchase orders
        $paymentsSent += \App\Models\PurchaseOrder::where('supplier_id', $recordId)
            ->where('down_payment', '>', 0)
            ->sum('down_payment');

        // Add Expenses (Biaya) - Paid Portion Only
        $expenses = \App\Models\Expense::where('contact_id', $recordId)->get();
        $paymentsSent += $expenses->sum(function ($expense) {
            return (float) ($expense->total_amount - ($expense->remaining_amount ?? 0));
        });

        return [
            Stat::make('Hutang Anda jatuh tempo', 'Rp ' . number_format($yourOverdue, 0, ',', '.'))
                ->description('Segera bayar sebelum denda')
                ->descriptionIcon('heroicon-m-clock')
                ->color('info'),
            Stat::make('Hutang mereka jatuh tempo', 'Rp ' . number_format($theirOverdue, 0, ',', '.'))
                ->description('Segera tagih pembayaran')
                ->descriptionIcon('heroicon-m-exclamation-circle')
                ->color('danger'),
            Stat::make('Pembayaran dikirim', 'Rp ' . number_format($paymentsSent, 0, ',', '.'))
                ->description('Total pengeluaran ke kontak')
                ->descriptionIcon('heroicon-m-paper-airplane')
                ->color('success'),
        ];
    }
}
