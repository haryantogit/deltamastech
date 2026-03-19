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
use Filament\Forms\Contracts\HasForms;
use Filament\Actions\Concerns\InteractsWithActions;
use Illuminate\Contracts\View\View;

class RingkasanBank extends Page implements HasForms
{
    use InteractsWithActions;

    public string $statsFilter = 'bulan';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-library';

    protected string $view = 'filament.pages.laporan.ringkasan-bank';

    protected static ?string $title = 'Ringkasan Bank';

    protected static ?string $slug = 'ringkasan-bank';

    protected static bool $shouldRegisterNavigation = false;

    public array $filters = [];

    public function setFilterBulan(): void
    {
        $this->statsFilter = 'bulan';
        $this->filters['startDate'] = now()->startOfYear()->toDateString();
        $this->filters['endDate'] = now()->toDateString();
        $this->dispatch('updateStatsFilter', 'bulan');
    }

    public function setFilterTahun(): void
    {
        $this->statsFilter = 'tahun';
        $this->filters['startDate'] = now()->startOfYear()->toDateString();
        $this->filters['endDate'] = now()->toDateString();
        $this->dispatch('updateStatsFilter', 'tahun');
    }

    public function mount(): void
    {
        $this->filters = [
            'startDate' => now()->startOfYear()->toDateString(),
            'endDate' => now()->toDateString(),
        ];
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            \App\Filament\Pages\ReportPage::getUrl() => 'Laporan',
            'Ringkasan Bank',
        ];
    }

    public function getSubheading(): \Illuminate\Contracts\Support\Htmlable|string|null
    {
        $startDate = $this->filters['startDate'] ?? now()->startOfYear()->toDateString();
        $endDate = $this->filters['endDate'] ?? now()->toDateString();
        $startFmt = \Carbon\Carbon::parse($startDate)->format('d/m/Y');
        $endFmt = \Carbon\Carbon::parse($endDate)->format('d/m/Y');

        $dateDisplay = $startFmt === $endFmt
            ? $startFmt
            : $startFmt . ' &mdash; ' . $endFmt;

        return new \Illuminate\Support\HtmlString('
            <div style="display: inline-flex; align-items: center; gap: 0.5rem; background-color: #f8fafc; padding: 0.5rem 1rem; border-radius: 0.5rem; border: 1px solid #e2e8f0; font-size: 0.875rem; font-weight: 600; color: #475569;" class="dark:bg-white/5 dark:border-white/10 dark:text-gray-300">
                <svg style="width: 1.25rem; height: 1.25rem; opacity: 0.7;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span>' . $dateDisplay . '</span>
            </div>
        ');
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
            Action::make('filter')
                ->label('Filter')
                ->icon('heroicon-m-funnel')
                ->color('gray')
                ->form([
                    \Filament\Forms\Components\DatePicker::make('startDate')
                        ->hiddenLabel()
                        ->default($this->filters['startDate'])
                        ->required(),
                    \Filament\Forms\Components\DatePicker::make('endDate')
                        ->hiddenLabel()
                        ->default($this->filters['endDate'])
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $this->filters['startDate'] = $data['startDate'];
                    $this->filters['endDate'] = $data['endDate'];
                    $this->statsFilter = 'custom';
                }),
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

                $start = Carbon::parse($this->filters['startDate'] ?? now()->startOfYear()->toDateString());
                $end = Carbon::parse($this->filters['endDate'] ?? now()->toDateString());

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
        $start = Carbon::parse($this->filters['startDate'] ?? now()->startOfYear()->toDateString());
        $end = Carbon::parse($this->filters['endDate'] ?? now()->toDateString());

        $dateDisplay = $start->isSameDay($end)
            ? $start->format('d/m/Y')
            : $start->format('d/m/Y') . ' &mdash; ' . $end->format('d/m/Y');

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
            'dateDisplay' => $dateDisplay,
            'rows' => $rows,
            'totalSaldoAwal' => $totalSaldoAwal,
            'totalMasuk' => $totalMasuk,
            'totalKeluar' => $totalKeluar,
            'totalSaldoAkhir' => $totalSaldoAkhir,
        ];
    }
}

