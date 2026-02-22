<?php

namespace App\Filament\Resources\ContactResource\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Model;

class ContactCashFlowChart extends ChartWidget
{
    public function getHeading(): ?string
    {
        return match ($this->record?->type) {
            'customer' => 'Penjualan',
            default => 'Hutang & Piutang',
        };
    }

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
        $dataDebt = array_fill(0, 12, 0); // Hutang
        $dataReceivable = array_fill(0, 12, 0); // Piutang

        $type = $this->record?->type;

        if ($this->record) {
            $year = now()->year;

            if ($type === 'customer') {
                // Get Sales Invoices for customer
                $invoices = \App\Models\SalesInvoice::where('contact_id', $this->record->id)
                    ->whereYear('transaction_date', $year)
                    ->get()
                    ->groupBy(function ($invoice) {
                        return \Carbon\Carbon::parse($invoice->transaction_date)->month;
                    });

                foreach ($invoices as $month => $monthInvoices) {
                    $dataReceivable[$month - 1] = (float) $monthInvoices->sum('total_amount');
                }
            } else {
                // Get Debts (Purchase Invoices + Independent Debts)
                $purchaseInvoices = \App\Models\PurchaseInvoice::where('supplier_id', $this->record->id)
                    ->whereYear('date', $year)
                    ->get()
                    ->groupBy(function ($invoice) {
                        return \Carbon\Carbon::parse($invoice->date)->month;
                    });

                foreach ($purchaseInvoices as $month => $monthInvoices) {
                    $dataDebt[$month - 1] += (float) $monthInvoices->sum('total_amount');
                }

                $independentDebts = \App\Models\Debt::where('supplier_id', $this->record->id)
                    ->whereYear('date', $year)
                    ->get()
                    ->groupBy(function ($debt) {
                        return \Carbon\Carbon::parse($debt->date)->month;
                    });

                foreach ($independentDebts as $month => $monthDebts) {
                    $dataDebt[$month - 1] += (float) $monthDebts->sum('amount');
                }

                // Get Unpaid Expenses (Hutang)
                $expenses = \App\Models\Expense::where('contact_id', $this->record->id)
                    ->where('is_pay_later', true)
                    ->whereYear('transaction_date', $year)
                    ->get()
                    ->groupBy(function ($expense) {
                        return \Carbon\Carbon::parse($expense->transaction_date)->month;
                    });

                foreach ($expenses as $month => $expenseItems) {
                    $dataDebt[$month - 1] += (float) $expenseItems->sum('total_amount');
                }

                // Get Receivables for non-customer (e.g. Employee cash bon)
                $independentReceivables = \App\Models\Receivable::where('contact_id', $this->record->id)
                    ->whereYear('transaction_date', $year)
                    ->get()
                    ->groupBy(function ($receivable) {
                        return \Carbon\Carbon::parse($receivable->transaction_date)->month;
                    });

                foreach ($independentReceivables as $month => $monthReceivables) {
                    $dataReceivable[$month - 1] += (float) $monthReceivables->sum('total_amount');
                }
            }
        }

        $datasets = [];

        if ($type === 'customer') {
            $datasets[] = [
                'label' => 'Penjualan',
                'data' => $dataReceivable,
                'backgroundColor' => 'rgba(16, 185, 129, 0.1)', // Emerald light fill
                'borderColor' => '#10b981', // Emerald solid line
                'fill' => 'start',
                'tension' => 0.4,
                'borderWidth' => 3,
                'pointRadius' => 3,
                'pointBackgroundColor' => '#fff',
                'pointBorderWidth' => 2,
            ];
        } else {
            $datasets[] = [
                'label' => 'Hutang',
                'data' => $dataDebt,
                'backgroundColor' => 'rgba(244, 63, 94, 0.1)', // Rose light fill
                'borderColor' => '#f43f5e', // Rose solid line
                'fill' => 'start',
                'tension' => 0.4,
                'borderWidth' => 3,
                'pointRadius' => 3,
                'pointBackgroundColor' => '#fff',
                'pointBorderWidth' => 2,
            ];
            $datasets[] = [
                'label' => 'Piutang',
                'data' => $dataReceivable,
                'backgroundColor' => 'rgba(59, 130, 246, 0.1)', // Blue light fill
                'borderColor' => '#3b82f6', // Blue solid line
                'fill' => 'start',
                'tension' => 0.4,
                'borderWidth' => 3,
                'pointRadius' => 3,
                'pointBackgroundColor' => '#fff',
                'pointBorderWidth' => 2,
            ];
        }

        return [
            'datasets' => $datasets,
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
