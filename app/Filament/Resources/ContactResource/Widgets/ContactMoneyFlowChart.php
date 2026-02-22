<?php

namespace App\Filament\Resources\ContactResource\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Model;

class ContactMoneyFlowChart extends ChartWidget
{
    protected ?string $heading = 'Keluar Masuk Uang';

    public function getDescription(): ?string
    {
        return 'Data arus kas tahun ' . now()->year;
    }

    protected ?string $maxHeight = '300px';

    public ?Model $record = null;

    protected function getData(): array
    {
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

        // Initialize with zeros
        $cashIn = array_fill(0, 12, 0);
        $cashOut = array_fill(0, 12, 0);

        if ($this->record) {
            $year = now()->year;

            // Uang Keluar: Only count actual payments made (debt payments + down payments)
            // Note: Don't count paid invoice totals, as that would double-count with debt payments

            // Debt payments
            $debtPayments = \App\Models\DebtPayment::whereHas('debt', fn($q) => $q->where('supplier_id', $this->record->id))
                ->whereYear('date', $year)
                ->get()
                ->groupBy(function ($payment) {
                    return \Carbon\Carbon::parse($payment->date)->month;
                });

            foreach ($debtPayments as $month => $payments) {
                $cashOut[$month - 1] += (float) $payments->sum('amount');
            }

            // Down payments from purchase orders
            $purchaseOrders = \App\Models\PurchaseOrder::where('supplier_id', $this->record->id)
                ->where('down_payment', '>', 0)
                ->whereYear('date', $year)
                ->get()
                ->groupBy(function ($order) {
                    return \Carbon\Carbon::parse($order->date)->month;
                });

            foreach ($purchaseOrders as $month => $orders) {
                $cashOut[$month - 1] += (float) $orders->sum('down_payment');
            }

            // Uang Masuk: Payments received from this customer (paid sales invoices + independent receivable payments)
            $salesInvoices = \App\Models\SalesInvoice::where('contact_id', $this->record->id)
                ->where('status', 'paid')
                ->whereYear('transaction_date', $year)
                ->get()
                ->groupBy(function ($invoice) {
                    return \Carbon\Carbon::parse($invoice->transaction_date)->month;
                });

            foreach ($salesInvoices as $month => $invoices) {
                $cashIn[$month - 1] += (float) $invoices->sum('total_amount');
            }

            // Include Expenses (Biaya) in CashOut
            $expenses = \App\Models\Expense::where('contact_id', $this->record->id)
                ->whereYear('transaction_date', $year)
                ->get()
                ->groupBy(function ($expense) {
                    return \Carbon\Carbon::parse($expense->transaction_date)->month;
                });

            foreach ($expenses as $month => $expenseItems) {
                // Calculate paid amount: Total - Remaining (if pay later) or Total (if paid immediately)
                $amount = $expenseItems->sum(function ($expense) {
                    return (float) ($expense->total_amount - ($expense->remaining_amount ?? 0));
                });
                $cashOut[$month - 1] += $amount;
            }

            // Add independent receivable payments
            $receivablePayments = \App\Models\ReceivablePayment::whereHas('receivable', fn($q) => $q->where('contact_id', $this->record->id))
                ->whereYear('date', $year)
                ->get()
                ->groupBy(function ($payment) {
                    return \Carbon\Carbon::parse($payment->date)->month;
                });

            foreach ($receivablePayments as $month => $payments) {
                $cashIn[$month - 1] += (float) $payments->sum('amount');
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Uang Masuk',
                    'data' => $cashIn,
                    'backgroundColor' => 'rgba(14, 165, 233, 0.1)', // Sky light fill
                    'borderColor' => '#0ea5e9', // Sky solid line
                    'fill' => 'start',
                    'tension' => 0.4,
                    'borderWidth' => 3,
                    'pointRadius' => 3,
                    'pointBackgroundColor' => '#fff',
                    'pointBorderWidth' => 2,
                ],
                [
                    'label' => 'Uang Keluar',
                    'data' => $cashOut,
                    'backgroundColor' => 'rgba(245, 158, 11, 0.1)', // Amber light fill
                    'borderColor' => '#f59e0b', // Amber solid line
                    'fill' => 'start',
                    'tension' => 0.4,
                    'borderWidth' => 3,
                    'pointRadius' => 3,
                    'pointBackgroundColor' => '#fff',
                    'pointBorderWidth' => 2,
                ],
            ],
            'labels' => $months,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                    'labels' => [
                        'usePointStyle' => true,
                        'padding' => 20,
                    ],
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => "function(value) { return 'Rp ' + value.toLocaleString('id-ID'); }",
                    ],
                ],
            ],
        ];
    }
}
