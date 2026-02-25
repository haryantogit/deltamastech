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
        $end = Carbon::now();
        $start = $end->copy()->startOfMonth();
        $today = $end->format('d/m/Y');

        $prevEnd = $start->copy()->subDay();
        $prevStart = $prevEnd->copy()->startOfMonth();

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
        if (!empty($kasBankIds)) {
            $kasIn = (float) JournalItem::whereIn('account_id', $kasBankIds)
                ->whereHas('journalEntry', fn($q) => $q->whereBetween('transaction_date', [$start, $end]))
                ->sum('debit');
            $kasOut = (float) JournalItem::whereIn('account_id', $kasBankIds)
                ->whereHas('journalEntry', fn($q) => $q->whereBetween('transaction_date', [$start, $end]))
                ->sum('credit');
        }
        $kasTotal = $kasIn - $kasOut;

        // ===== PROFITABILITAS =====
        $pendapatan = $this->sumByCategoriesPeriod(['Pendapatan', 'Pendapatan Lainnya'], $start, $end, 'credit - debit');
        $hpp = $this->sumByCategoriesPeriod(['Harga Pokok Penjualan'], $start, $end, 'debit - credit');
        $labaKotor = $pendapatan - $hpp;
        $biaya = $this->sumByCategoriesPeriod(['Beban', 'Beban Lainnya'], $start, $end, 'debit - credit');
        // $labaBersih already calculated above

        // ===== PERFORMA =====
        $marginLabaKotor = $pendapatan != 0 ? round(($labaKotor / $pendapatan) * 100, 1) : 0;
        $marginLabaBersih = $pendapatan != 0 ? round(($labaBersih / $pendapatan) * 100, 1) : 0;

        // ===== POSISI =====
        $rasioAsetKewajiban = $totalKewajibanLancar != 0 ? round($totalAsetLancar / $totalKewajibanLancar, 2) : 0;
        $rasioHutangEkuitas = $ekuitas != 0 ? round($totalLiabilitas / $ekuitas, 2) : 0;
        $rasioHutangAset = $totalAset != 0 ? round($totalLiabilitas / $totalAset, 2) : 0;
        $rasioAsetLiabilitas = $totalLiabilitas != 0 ? round($totalAset / $totalLiabilitas, 2) : 0;

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

        return [
            'today' => $today,
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
            // Profitabilitas
            'pendapatan' => $pendapatan,
            'hpp' => $hpp,
            'labaKotor' => $labaKotor,
            'biaya' => $biaya,
            'labaBersih' => $labaBersih,
            // Neraca summary
            'totalAset' => $totalAset,
            'totalLiabilitas' => $totalLiabilitas,
            'ekuitas' => $ekuitas,
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
            // Posisi
            'rasioAsetKewajiban' => $rasioAsetKewajiban,
            'rasioHutangEkuitas' => $rasioHutangEkuitas,
            'rasioHutangAset' => $rasioHutangAset,
            'rasioAsetLiabilitas' => $rasioAsetLiabilitas,
        ];
    }
}
