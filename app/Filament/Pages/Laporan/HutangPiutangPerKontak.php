<?php

namespace App\Filament\Pages\Laporan;

use App\Models\Contact;
use App\Models\Debt;
use App\Models\DebtPayment;
use App\Models\Receivable;
use App\Models\ReceivablePayment;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class HutangPiutangPerKontak extends Page
{

    protected string $view = 'filament.pages.laporan.hutang-piutang-per-kontak';

    protected static ?string $title = 'Hutang Piutang per Kontak';

    protected static ?string $slug = 'hutang-piutang-per-kontak';

    protected static bool $shouldRegisterNavigation = false;

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            \App\Filament\Pages\ReportPage::getUrl() => 'Laporan',
            'Hutang Piutang per Kontak',
        ];
    }

    public function getMaxContentWidth(): string
    {
        return 'full';
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\HutangPiutangStatsWidget::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('panduan')
                ->label('Panduan')
                ->color('gray')
                ->icon('heroicon-o-question-mark-circle')
                ->url('#'),
            Action::make('ekspor')
                ->label('Ekspor')
                ->color('gray')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->url('#'),
            Action::make('bagikan')
                ->label('Bagikan')
                ->color('gray')
                ->icon('heroicon-o-share')
                ->url('#'),
            Action::make('print')
                ->label('Print')
                ->color('gray')
                ->icon('heroicon-o-printer')
                ->url('#'),
            Action::make('kembali')
                ->label('Kembali')
                ->color('gray')
                ->icon('heroicon-o-arrow-left')
                ->url(\App\Filament\Pages\ReportPage::getUrl()),
        ];
    }

    public function getViewData(): array
    {
        $today = Carbon::now()->format('d/m/Y');

        // Get invoice numbers to exclude (those created from sales/purchase invoices)
        $purchaseInvoiceNumbers = \App\Models\PurchaseInvoice::pluck('number')->toArray();
        $salesInvoiceNumbers = \App\Models\SalesInvoice::pluck('invoice_number')->toArray();

        // Get all contacts that have debts or receivables (excluding invoice-generated ones)
        $contactsWithDebt = Debt::whereNotIn('reference', $purchaseInvoiceNumbers)
            ->select('supplier_id')
            ->groupBy('supplier_id')
            ->pluck('supplier_id')
            ->toArray();

        $contactsWithReceivable = Receivable::whereNotIn('invoice_number', $salesInvoiceNumbers)
            ->select('contact_id')
            ->groupBy('contact_id')
            ->pluck('contact_id')
            ->toArray();

        $allContactIds = array_unique(array_merge($contactsWithDebt, $contactsWithReceivable));
        $contacts = Contact::whereIn('id', $allContactIds)->orderBy('name')->get();

        $rows = [];
        $totalHutang = 0;
        $totalPiutang = 0;

        foreach ($contacts as $contact) {
            // --- HUTANG (Debts) --- only from Hutang menu, not from purchase invoices
            $debts = Debt::where('supplier_id', $contact->id)
                ->whereNotIn('reference', $purchaseInvoiceNumbers)
                ->get();
            $hutangTotal = 0;
            $debtDetails = [];

            foreach ($debts as $debt) {
                $paid = $debt->payments()->sum('amount');
                $outstanding = (float) $debt->total_amount - (float) $paid;
                if (abs($outstanding) < 0.01)
                    continue;

                $hutangTotal += $outstanding;
                $debtDetails[] = [
                    'tanggal' => $debt->date ? Carbon::parse($debt->date)->format('d/m/Y') : '-',
                    'nomor' => $debt->number ?? '-',
                    'deskripsi' => $debt->reference ?? $debt->notes ?? '-',
                    'hutang' => $outstanding,
                    'piutang' => 0,
                    'net' => -$outstanding,
                ];
            }

            // --- PIUTANG (Receivables) --- only from Piutang menu, not from sales invoices
            $receivables = Receivable::where('contact_id', $contact->id)
                ->whereNotIn('invoice_number', $salesInvoiceNumbers)
                ->get();
            $piutangTotal = 0;
            $receivableDetails = [];

            foreach ($receivables as $receivable) {
                $paid = $receivable->payments()->sum('amount');
                $outstanding = (float) $receivable->total_amount - (float) $paid;
                if (abs($outstanding) < 0.01)
                    continue;

                $piutangTotal += $outstanding;
                $receivableDetails[] = [
                    'tanggal' => $receivable->transaction_date ? Carbon::parse($receivable->transaction_date)->format('d/m/Y') : '-',
                    'nomor' => $receivable->invoice_number ?? '-',
                    'deskripsi' => $receivable->reference ?? $receivable->notes ?? '-',
                    'hutang' => 0,
                    'piutang' => $outstanding,
                    'net' => $outstanding,
                ];
            }

            if ($hutangTotal == 0 && $piutangTotal == 0)
                continue;

            $net = $piutangTotal - $hutangTotal;

            $totalHutang += $hutangTotal;
            $totalPiutang += $piutangTotal;

            // Merge and sort details by date
            $details = array_merge($debtDetails, $receivableDetails);
            usort($details, fn($a, $b) => strcmp($a['tanggal'], $b['tanggal']));

            $rows[] = [
                'contact' => $contact->name,
                'hutang' => $hutangTotal,
                'piutang' => $piutangTotal,
                'net' => $net,
                'details' => $details,
            ];
        }

        $netTotal = $totalPiutang - $totalHutang;

        return [
            'today' => $today,
            'rows' => $rows,
            'totalHutang' => $totalHutang,
            'totalPiutang' => $totalPiutang,
            'netTotal' => $netTotal,
        ];
    }
}
