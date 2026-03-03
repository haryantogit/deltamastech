<?php

namespace App\Filament\Pages\Laporan;

use App\Models\Account;
use App\Models\JournalItem;
use App\Models\PurchaseInvoice;
use App\Models\SalesInvoice;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class RingkasanEksekutif extends Page
{
    public string $statsFilter = 'bulan';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected string $view = 'filament.pages.laporan.ringkasan-eksekutif';

    protected static ?string $title = 'Ringkasan Eksekutif';

    protected static ?string $slug = 'ringkasan-eksekutif';

    protected static bool $shouldRegisterNavigation = false;

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            \App\Filament\Pages\ReportPage::getUrl() => 'Laporan',
            'Ringkasan Eksekutif',
        ];
    }

    public function getMaxContentWidth(): string
    {
        return 'full';
    }

    public ?string $startDate = null;
    public ?string $endDate = null;

    public function mount(): void
    {
        if (!$this->startDate) {
            $this->startDate = now()->startOfYear()->toDateString();
        }
        if (!$this->endDate) {
            $this->endDate = now()->toDateString();
        }
    }

    public function getSubheading(): \Illuminate\Contracts\Support\Htmlable|string|null
    {
        $startDate = $this->startDate ?? now()->startOfYear()->toDateString();
        $endDate = $this->endDate ?? now()->toDateString();
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
                        ->default($this->startDate)
                        ->required(),
                    \Filament\Forms\Components\DatePicker::make('endDate')
                        ->hiddenLabel()
                        ->default($this->endDate)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $this->startDate = $data['startDate'];
                    $this->endDate = $data['endDate'];
                    $this->statsFilter = 'custom';
                }),
            Action::make('print')
                ->label('Print')
                ->color('gray')
                ->icon('heroicon-o-printer')
                ->action(fn() => $this->js('window.print()')),
            Action::make('kembali')
                ->label('Kembali')
                ->color('gray')
                ->icon('heroicon-o-arrow-left')
                ->url(\App\Filament\Pages\ReportPage::getUrl()),
        ];
    }

    private function sumByCategories(array $categories, Carbon $endDate, string $expr = 'debit - credit'): float
    {
        $ids = Account::whereIn('category', $categories)->pluck('id')->toArray();
        if (empty($ids))
            return 0;
        return (float) JournalItem::whereIn('account_id', $ids)
            ->whereHas('journalEntry', fn($q) => $q->where('transaction_date', '<=', $endDate))
            ->sum(DB::raw($expr));
    }

    private function sumByCategoriesPeriod(array $categories, Carbon $start, Carbon $end, string $expr = 'debit - credit'): float
    {
        $ids = Account::whereIn('category', $categories)->pluck('id')->toArray();
        if (empty($ids))
            return 0;
        return (float) JournalItem::whereIn('account_id', $ids)
            ->whereHas('journalEntry', fn($q) => $q->whereBetween('transaction_date', [$start, $end]))
            ->sum(DB::raw($expr));
    }

    public function getViewData(): array
    {
        $start = Carbon::parse($this->startDate);
        $end = Carbon::parse($this->endDate);
        $today = $end->format('d/m/Y');

        $daysCount = $start->diffInDays($end) + 1;
        $prevEnd = $start->copy()->subDay();
        $prevStart = $prevEnd->copy()->subDays($daysCount - 1);

        $activeFilter = $this->statsFilter;
        $filterLabel = 'vs periode sebelumnya';

        // ===== NERACA RATIOS =====
        $kasBank = $this->sumByCategories(['Kas & Bank'], $end);
        $piutang = $this->sumByCategories(['Akun Piutang'], $end);
        $persediaan = $this->sumByCategories(['Persediaan'], $end);
        $aktivaLancarLain = $this->sumByCategories(['Aktiva Lancar Lainnya'], $end);
        $asetTetap = $this->sumByCategories(['Aktiva Tetap'], $end);
        $depresiasi = $this->sumByCategories(['Depresiasi & Amortisasi'], $end);
        $aktivaLain = $this->sumByCategories(['Aktiva Lainnya'], $end);

        $totalAsetLancar = $kasBank + $piutang + $persediaan + $aktivaLancarLain;
        $totalAset = $totalAsetLancar + $asetTetap + $depresiasi + $aktivaLain;

        $hutang = $this->sumByCategories(['Akun Hutang'], $end, 'credit - debit');
        $kewajibanLancar = $this->sumByCategories(['Kewajiban Lancar Lainnya'], $end, 'credit - debit');
        $kewajibanPanjang = $this->sumByCategories(['Kewajiban Jangka Panjang'], $end, 'credit - debit');

        $totalKewajibanLancar = $hutang + $kewajibanLancar;
        $totalLiabilitas = $totalKewajibanLancar + $kewajibanPanjang;

        $ekuitas = $this->sumByCategories(['Ekuitas'], $end, 'credit - debit');

        $quickRatio = $totalKewajibanLancar != 0 ? round(($kasBank + $piutang) / $totalKewajibanLancar, 1) : 0;
        $currentRatio = $totalKewajibanLancar != 0 ? round($totalAsetLancar / $totalKewajibanLancar, 1) : 0;
        $debtEquityRatio = $ekuitas != 0 ? round($totalLiabilitas / $ekuitas, 1) : 0;
        $equityRatio = $totalAset != 0 ? round($ekuitas / $totalAset, 1) : 0;

        // ROI
        $labaBersih = $this->sumByCategoriesPeriod(['Pendapatan', 'Pendapatan Lainnya'], $start, $end, 'credit - debit')
            - $this->sumByCategoriesPeriod(['Harga Pokok Penjualan', 'Beban', 'Beban Lainnya'], $start, $end, 'debit - credit');
        $roi = $totalAset != 0 ? round(($labaBersih / $totalAset) * 100, 2) : 0;

        // Previous period ratios
        $prevKasBank = $this->sumByCategories(['Kas & Bank'], $prevEnd);
        $prevPiutang = $this->sumByCategories(['Akun Piutang'], $prevEnd);
        $prevPersediaan = $this->sumByCategories(['Persediaan'], $prevEnd);
        $prevAktivaLancarLain = $this->sumByCategories(['Aktiva Lancar Lainnya'], $prevEnd);
        $prevTotalAsetLancar = $prevKasBank + $prevPiutang + $prevPersediaan + $prevAktivaLancarLain;
        $prevTotalAset = $prevTotalAsetLancar + $this->sumByCategories(['Aktiva Tetap'], $prevEnd) + $this->sumByCategories(['Depresiasi & Amortisasi'], $prevEnd) + $this->sumByCategories(['Aktiva Lainnya'], $prevEnd);

        $prevHutang = $this->sumByCategories(['Akun Hutang'], $prevEnd, 'credit - debit');
        $prevKewajibanLancar = $this->sumByCategories(['Kewajiban Lancar Lainnya'], $prevEnd, 'credit - debit');
        $prevTotalKewajibanLancar = $prevHutang + $prevKewajibanLancar;
        $prevKewajibanPanjang = $this->sumByCategories(['Kewajiban Jangka Panjang'], $prevEnd, 'credit - debit');
        $prevTotalLiabilitas = $prevTotalKewajibanLancar + $prevKewajibanPanjang;

        $prevEkuitas = $this->sumByCategories(['Ekuitas'], $prevEnd, 'credit - debit');

        $prevQuickRatio = $prevTotalKewajibanLancar != 0 ? round(($prevKasBank + $prevPiutang) / $prevTotalKewajibanLancar, 1) : 0;
        $prevCurrentRatio = $prevTotalKewajibanLancar != 0 ? round($prevTotalAsetLancar / $prevTotalKewajibanLancar, 1) : 0;
        $prevDebtEquityRatio = $prevEkuitas != 0 ? round($prevTotalLiabilitas / $prevEkuitas, 1) : 0;
        $prevEquityRatio = $prevTotalAset != 0 ? round($prevEkuitas / $prevTotalAset, 1) : 0;

        $prevLabaBersih = $this->sumByCategoriesPeriod(['Pendapatan', 'Pendapatan Lainnya'], $prevStart, $prevEnd, 'credit - debit')
            - $this->sumByCategoriesPeriod(['Harga Pokok Penjualan', 'Beban', 'Beban Lainnya'], $prevStart, $prevEnd, 'debit - credit');
        $prevRoi = $prevTotalAset != 0 ? round(($prevLabaBersih / $prevTotalAset) * 100, 2) : 0;

        // ===== PERUBAHAN MODAL =====
        $saldoModal = $ekuitas;
        $penambahanModal = $this->sumByCategoriesPeriod(['Ekuitas'], $start, $end, 'credit');
        $penguranganModal = $this->sumByCategoriesPeriod(['Ekuitas'], $start, $end, 'debit');
        $perubahanModal = $penambahanModal - $penguranganModal;

        $prevSaldoModal = $prevEkuitas;
        $prevPenambahanModal = $this->sumByCategoriesPeriod(['Ekuitas'], $prevStart, $prevEnd, 'credit');
        $prevPenguranganModal = $this->sumByCategoriesPeriod(['Ekuitas'], $prevStart, $prevEnd, 'debit');
        $prevPerubahanModal = $prevPenambahanModal - $prevPenguranganModal;

        // ===== KAS =====
        $kasBankIds = Account::where('category', 'Kas & Bank')->pluck('id')->toArray();
        $kasIn = 0;
        $kasOut = 0;
        $prevKasIn = 0;
        $prevKasOut = 0;
        if (!empty($kasBankIds)) {
            $kasIn = (float) JournalItem::whereIn('account_id', $kasBankIds)
                ->whereHas('journalEntry', fn($q) => $q->whereBetween('transaction_date', [$start, $end]))
                ->sum('debit');
            $kasOut = (float) JournalItem::whereIn('account_id', $kasBankIds)
                ->whereHas('journalEntry', fn($q) => $q->whereBetween('transaction_date', [$start, $end]))
                ->sum('credit');
            $prevKasIn = (float) JournalItem::whereIn('account_id', $kasBankIds)
                ->whereHas('journalEntry', fn($q) => $q->whereBetween('transaction_date', [$prevStart, $prevEnd]))
                ->sum('debit');
            $prevKasOut = (float) JournalItem::whereIn('account_id', $kasBankIds)
                ->whereHas('journalEntry', fn($q) => $q->whereBetween('transaction_date', [$prevStart, $prevEnd]))
                ->sum('credit');
        }
        $kasTotal = $kasIn - $kasOut;
        $prevKasTotal = $prevKasIn - $prevKasOut;

        // ===== PROFITABILITAS =====
        $pendapatan = $this->sumByCategoriesPeriod(['Pendapatan', 'Pendapatan Lainnya'], $start, $end, 'credit - debit');
        $hpp = $this->sumByCategoriesPeriod(['Harga Pokok Penjualan'], $start, $end, 'debit - credit');
        $labaKotor = $pendapatan - $hpp;
        $biaya = $this->sumByCategoriesPeriod(['Beban', 'Beban Lainnya'], $start, $end, 'debit - credit');
        // $labaBersih already calculated above

        $prevPendapatan = $this->sumByCategoriesPeriod(['Pendapatan', 'Pendapatan Lainnya'], $prevStart, $prevEnd, 'credit - debit');
        $prevHpp = $this->sumByCategoriesPeriod(['Harga Pokok Penjualan'], $prevStart, $prevEnd, 'debit - credit');
        $prevLabaKotor = $prevPendapatan - $prevHpp;
        $prevBiaya = $this->sumByCategoriesPeriod(['Beban', 'Beban Lainnya'], $prevStart, $prevEnd, 'debit - credit');

        // ===== PERFORMA =====
        $marginLabaKotor = $pendapatan != 0 ? round(($labaKotor / $pendapatan) * 100, 1) : 0;
        $marginLabaBersih = $pendapatan != 0 ? round(($labaBersih / $pendapatan) * 100, 1) : 0;

        $prevMarginLabaKotor = $prevPendapatan != 0 ? round(($prevLabaKotor / $prevPendapatan) * 100, 1) : 0;
        $prevMarginLabaBersih = $prevPendapatan != 0 ? round(($prevLabaBersih / $prevPendapatan) * 100, 1) : 0;

        // ===== POSISI =====
        $rasioAsetKewajiban = $totalKewajibanLancar != 0 ? round($totalAsetLancar / $totalKewajibanLancar, 2) : 0;
        $rasioHutangEkuitas = $ekuitas != 0 ? round($totalLiabilitas / $ekuitas, 2) : 0;
        $rasioHutangAset = $totalAset != 0 ? round($totalLiabilitas / $totalAset, 2) : 0;
        $rasioAsetLiabilitas = $totalLiabilitas != 0 ? round($totalAset / $totalLiabilitas, 2) : 0;

        $prevRasioAsetKewajiban = $prevTotalKewajibanLancar != 0 ? round($prevTotalAsetLancar / $prevTotalKewajibanLancar, 2) : 0;
        $prevRasioHutangEkuitas = $prevEkuitas != 0 ? round($prevTotalLiabilitas / $prevEkuitas, 2) : 0;
        $prevRasioHutangAset = $prevTotalAset != 0 ? round($prevTotalLiabilitas / $prevTotalAset, 2) : 0;
        $prevRasioAsetLiabilitas = $prevTotalLiabilitas != 0 ? round($prevTotalAset / $prevTotalLiabilitas, 2) : 0;

        // ===== PENDAPATAN STATS =====
        $jumlahInvoice = SalesInvoice::whereBetween('transaction_date', [$start, $end])->count();
        $avgInvoice = SalesInvoice::whereBetween('transaction_date', [$start, $end])->avg('total_amount') ?? 0;
        $prevJumlahInvoice = SalesInvoice::whereBetween('transaction_date', [$prevStart, $prevEnd])->count();
        $prevAvgInvoice = SalesInvoice::whereBetween('transaction_date', [$prevStart, $prevEnd])->avg('total_amount') ?? 0;

        // Average days AR conversion
        $avgDSO = 0;
        if ($pendapatan > 0) {
            $avgDSO = round(($piutang / ($pendapatan / 30)), 1);
        }

        // Average days AP conversion
        $totalPurchases = $this->sumByCategoriesPeriod(['Harga Pokok Penjualan'], $start, $end, 'debit - credit');
        $avgDPO = 0;
        if ($totalPurchases > 0) {
            $apBalance = $this->sumByCategories(['Akun Hutang'], $end, 'credit - debit');
            $avgDPO = round(($apBalance / ($totalPurchases / 30)), 1);
        }

        // Previous neraca summary
        $prevTotalAsetVal = $prevTotalAset;
        $prevTotalLiabilitasVal = $prevTotalLiabilitas;
        $prevEkuitasVal = $prevEkuitas;

        return [
            'today' => $today,
            'filterLabel' => $filterLabel,
            'startDate' => $start->format('d/m/Y'),
            'endDate' => $end->format('d/m/Y'),
            // Neraca Ratios
            'quickRatio' => $quickRatio,
            'currentRatio' => $currentRatio,
            'debtEquityRatio' => $debtEquityRatio,
            'equityRatio' => $equityRatio,
            'roi' => $roi,
            'prevQuickRatio' => $prevQuickRatio,
            'prevCurrentRatio' => $prevCurrentRatio,
            'prevDebtEquityRatio' => $prevDebtEquityRatio,
            'prevEquityRatio' => $prevEquityRatio,
            'prevRoi' => $prevRoi,
            // Perubahan Modal
            'perubahanModal' => $perubahanModal,
            'saldoModal' => $saldoModal,
            'penambahanModal' => $penambahanModal,
            'penguranganModal' => $penguranganModal,
            'prevPerubahanModal' => $prevPerubahanModal,
            'prevSaldoModal' => $prevSaldoModal,
            'prevPenambahanModal' => $prevPenambahanModal,
            'prevPenguranganModal' => $prevPenguranganModal,
            // Kas
            'kasIn' => $kasIn,
            'kasOut' => $kasOut,
            'kasTotal' => $kasTotal,
            'prevKasIn' => $prevKasIn,
            'prevKasOut' => $prevKasOut,
            'prevKasTotal' => $prevKasTotal,
            // Profitabilitas
            'pendapatan' => $pendapatan,
            'hpp' => $hpp,
            'labaKotor' => $labaKotor,
            'biaya' => $biaya,
            'labaBersih' => $labaBersih,
            'prevPendapatan' => $prevPendapatan,
            'prevHpp' => $prevHpp,
            'prevLabaKotor' => $prevLabaKotor,
            'prevBiaya' => $prevBiaya,
            'prevLabaBersih' => $prevLabaBersih,
            // Neraca summary
            'totalAset' => $totalAset,
            'totalLiabilitas' => $totalLiabilitas,
            'ekuitas' => $ekuitas,
            'prevTotalAset' => $prevTotalAsetVal,
            'prevTotalLiabilitas' => $prevTotalLiabilitasVal,
            'prevEkuitas' => $prevEkuitasVal,
            // Pendapatan
            'jumlahInvoice' => $jumlahInvoice,
            'avgInvoice' => $avgInvoice,
            'avgDSO' => $avgDSO,
            'avgDPO' => $avgDPO,
            'prevJumlahInvoice' => $prevJumlahInvoice,
            'prevAvgInvoice' => $prevAvgInvoice,
            // Performa
            'marginLabaKotor' => $marginLabaKotor,
            'marginLabaBersih' => $marginLabaBersih,
            'prevMarginLabaKotor' => $prevMarginLabaKotor,
            'prevMarginLabaBersih' => $prevMarginLabaBersih,
            // Posisi
            'rasioAsetKewajiban' => $rasioAsetKewajiban,
            'rasioHutangEkuitas' => $rasioHutangEkuitas,
            'rasioHutangAset' => $rasioHutangAset,
            'rasioAsetLiabilitas' => $rasioAsetLiabilitas,
            'prevRasioAsetKewajiban' => $prevRasioAsetKewajiban,
            'prevRasioHutangEkuitas' => $prevRasioHutangEkuitas,
            'prevRasioHutangAset' => $prevRasioHutangAset,
            'prevRasioAsetLiabilitas' => $prevRasioAsetLiabilitas,
        ];
    }
}

