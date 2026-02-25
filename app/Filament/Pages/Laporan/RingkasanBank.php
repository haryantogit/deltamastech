<?php

namespace App\Filament\Pages\Laporan;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

use Filament\Actions\Contracts\HasActions;
use Filament\Actions\Concerns\InteractsWithActions;
use Illuminate\Contracts\View\View;

class RingkasanBank extends Page implements HasActions
{
    use InteractsWithActions;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-library';

    protected string $view = 'filament.pages.laporan.ringkasan-bank';

    protected static ?string $title = 'Ringkasan Bank';

    protected static ?string $slug = 'ringkasan-bank';

    protected static bool $shouldRegisterNavigation = false;

    public ?string $startDate = null;
    public ?string $endDate = null;

    public function mount(): void
    {
        $this->startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->endDate = Carbon::now()->format('Y-m-d');
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            \App\Filament\Pages\ReportPage::getUrl() => 'Laporan',
            'Ringkasan Bank',
        ];
    }

    public function getMaxContentWidth(): string
    {
        return 'full';
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\RingkasanBankStatsWidget::class,
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

    public function viewTransaksiAction(): Action
    {
        return Action::make('viewTransaksi')
            ->label('Lihat Transaksi')
            ->modalHeading('') // Set to empty to use custom header in view
            ->modalSubmitAction(false)
            ->modalCancelAction(false)
            ->modalWidth('full')
            ->modalContent(function (array $arguments): View {
                $accountId = $arguments['account_id'] ?? null;
                $account = Account::find($accountId);

                if (!$account) {
                    abort(404);
                }

                $start = Carbon::parse($this->startDate);
                $end = Carbon::parse($this->endDate);

                // Calculate Saldo Awal (before start date)
                $saldoAwal = (float) JournalItem::where('account_id', $account->id)
                    ->whereHas('journalEntry', fn($q) => $q->where('transaction_date', '<', $start))
                    ->sum(DB::raw('debit - credit'));

                // Get transactions within the date range
                $journalItems = JournalItem::where('account_id', $account->id)
                    ->whereHas('journalEntry', fn($q) => $q->whereBetween('transaction_date', [$start, $end]))
                    ->with(['journalEntry'])
                    ->get()
                    ->sortBy(function ($item) {
                    return $item->journalEntry->transaction_date;
                });

                $transactions = [];
                foreach ($journalItems as $item) {
                    $entry = $item->journalEntry;

                    // Determine Source (Sumber) based on Reference Number
                    $sumber = 'Jurnal Umum';
                    $ref = (string) $entry->reference_number;
                    $desc = strtolower((string) $entry->description);

                    if (str_starts_with($ref, 'EXP/') || str_contains($desc, 'biaya')) {
                        $sumber = 'Biaya';
                    } elseif (str_starts_with($ref, 'TR/') || str_contains($desc, 'transfer')) {
                        $sumber = 'Transfer';
                    } elseif (str_starts_with($ref, 'PI/') || str_contains($desc, 'purchase')) {
                        $sumber = 'Pembelian';
                    } elseif (str_starts_with($ref, 'SI/') || str_contains($desc, 'sales')) {
                        $sumber = 'Penjualan';
                    } elseif (str_starts_with($ref, 'PAY') || str_starts_with($ref, 'PP/') || str_starts_with($ref, 'SP/')) {
                        $sumber = 'Pembayaran';
                    }

                    $transactions[] = [
                        'tanggal' => Carbon::parse($entry->transaction_date)->format('d/m/Y'),
                        'sumber' => $sumber,
                        'deskripsi' => $entry->description ?: '-',
                        'referensi' => $entry->memo ?: '-',
                        'nomor' => $entry->reference_number ?: '-',
                        'debit' => (float) $item->debit,
                        'kredit' => (float) $item->credit,
                    ];
                }

                return view('filament.pages.laporan.modal-transaksi-kas', [
                    'account' => $account,
                    'startFormatted' => $start->format('d/m/Y'),
                    'endFormatted' => $end->format('d/m/Y'),
                    'saldoAwal' => $saldoAwal,
                    'transactions' => $transactions,
                ]);
            });
    }

    public function getViewData(): array
    {
        $start = Carbon::parse($this->startDate);
        $end = Carbon::parse($this->endDate);
        $today = Carbon::now()->format('d/m/Y');

        // Get all Kas & Bank accounts
        $bankAccounts = Account::where('category', 'Kas & Bank')
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $rows = [];
        $totalSaldoAwal = 0;
        $totalMasuk = 0;
        $totalKeluar = 0;
        $totalSaldoAkhir = 0;

        foreach ($bankAccounts as $account) {
            // Saldo Awal: sum of all debit - credit BEFORE the start date
            $saldoAwal = (float) JournalItem::where('account_id', $account->id)
                ->whereHas('journalEntry', fn($q) => $q->where('transaction_date', '<', $start))
                ->sum(DB::raw('debit - credit'));

            // Uang Diterima (Masuk): sum of debit within the date range
            $masuk = (float) JournalItem::where('account_id', $account->id)
                ->whereHas('journalEntry', fn($q) => $q->whereBetween('transaction_date', [$start, $end]))
                ->sum('debit');

            // Uang Dibelanjakan (Keluar): sum of credit within the date range
            $keluar = (float) JournalItem::where('account_id', $account->id)
                ->whereHas('journalEntry', fn($q) => $q->whereBetween('transaction_date', [$start, $end]))
                ->sum('credit');

            // Saldo Akhir
            $saldoAkhir = $saldoAwal + $masuk - $keluar;

            $totalSaldoAwal += $saldoAwal;
            $totalMasuk += $masuk;
            $totalKeluar += $keluar;
            $totalSaldoAkhir += $saldoAkhir;

            $rows[] = [
                'id' => $account->id,
                'name' => $account->name,
                'code' => $account->code,
                'saldoAwal' => $saldoAwal,
                'masuk' => $masuk,
                'keluar' => $keluar,
                'saldoAkhir' => $saldoAkhir,
            ];
        }

        return [
            'today' => $today,
            'startFormatted' => $start->format('d/m/Y'),
            'endFormatted' => $end->format('d/m/Y'),
            'rows' => $rows,
            'totalSaldoAwal' => $totalSaldoAwal,
            'totalMasuk' => $totalMasuk,
            'totalKeluar' => $totalKeluar,
            'totalSaldoAkhir' => $totalSaldoAkhir,
        ];
    }
}
