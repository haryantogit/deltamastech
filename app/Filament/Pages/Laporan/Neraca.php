<?php

namespace App\Filament\Pages\Laporan;

use App\Models\Account;
use App\Models\JournalItem;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Illuminate\Support\Facades\DB;

class Neraca extends Page
{
    use HasFiltersForm;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected string $view = 'filament.pages.laporan.neraca';

    protected static ?string $title = 'Neraca';

    protected static ?string $slug = 'neraca';

    protected static bool $shouldRegisterNavigation = false;

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            \App\Filament\Pages\ReportPage::getUrl() => 'Laporan',
            'Neraca',
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

    public function filtersForm(Schema $form): Schema
    {
        return $form
            ->schema([
                DatePicker::make('endDate')
                    ->label('Tanggal')
                    ->default(now()->toDateString())
                    ->native(false)
                    ->displayFormat('d/m/Y'),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                EmbeddedSchema::make('filtersForm'),
            ]);
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\NeracaStatsWidget::class,
        ];
    }

    public function getViewData(): array
    {
        $endDate = $this->filters['endDate'] ?? now()->toDateString();
        $end = Carbon::parse($endDate);
        $today = $end->format('d/m/Y');

        // Helper to get account balance from journal entries up to a date
        $getBalanceByCategory = function (array $categories) use ($end) {
            $accounts = Account::whereIn('category', $categories)
                ->whereNull('parent_id')
                ->with('children')
                ->orderBy('code')
                ->get();

            $sections = [];
            $total = 0;

            foreach ($categories as $catKey => $catLabel) {
                $catAccounts = $accounts->where('category', $catKey)->values();
                $catTotal = 0;
                $rows = [];

                foreach ($catAccounts as $account) {
                    // Calculate balance from journal entries
                    $balance = (float) JournalItem::where('account_id', $account->id)
                        ->whereHas('journalEntry', fn($q) => $q->where('transaction_date', '<=', $end))
                        ->sum(DB::raw('debit - credit'));

                    $catTotal += $balance;

                    $children = [];
                    foreach ($account->children->sortBy('code') as $child) {
                        $childBalance = (float) JournalItem::where('account_id', $child->id)
                            ->whereHas('journalEntry', fn($q) => $q->where('transaction_date', '<=', $end))
                            ->sum(DB::raw('debit - credit'));

                        $children[] = [
                            'code' => $child->code,
                            'name' => $child->name,
                            'balance' => $childBalance,
                        ];
                    }

                    $rows[] = [
                        'code' => $account->code,
                        'name' => $account->name,
                        'balance' => $balance,
                        'children' => $children,
                    ];
                }

                $total += $catTotal;
                $sections[] = [
                    'label' => $catLabel,
                    'rows' => $rows,
                    'total' => $catTotal,
                ];
            }

            return ['sections' => $sections, 'total' => $total];
        };

        // Build all sections with dynamic data
        $kasBank = $getBalanceByCategory(['Kas & Bank' => 'Kas & Bank']);
        $piutang = $getBalanceByCategory(['Akun Piutang' => 'Akun Piutang']);
        $persediaan = $getBalanceByCategory(['Persediaan' => 'Persediaan']);
        $aktivaLancarLain = $getBalanceByCategory(['Aktiva Lancar Lainnya' => 'Aktiva Lancar Lainnya']);
        $asetTetap = $getBalanceByCategory(['Aktiva Tetap' => 'Aktiva Tetap']);
        $depresiasi = $getBalanceByCategory(['Depresiasi & Amortisasi' => 'Depresiasi & Amortisasi']);
        $hutang = $getBalanceByCategory(['Akun Hutang' => 'Akun Hutang']);
        $kewajibanLancar = $getBalanceByCategory(['Kewajiban Lancar Lainnya' => 'Kewajiban Lancar Lainnya']);
        $ekuitas = $getBalanceByCategory(['Ekuitas' => 'Ekuitas']);

        // Totals
        $totalAsetLancar = $kasBank['total'] + $piutang['total'] + $persediaan['total'] + $aktivaLancarLain['total'];
        $totalAsetTetap = $asetTetap['total'];
        $totalDepresiasi = $depresiasi['total'];
        $totalAset = $totalAsetLancar + $totalAsetTetap + $totalDepresiasi;

        $totalLiabilitasPendek = $hutang['total'] + $kewajibanLancar['total'];
        $totalModal = $ekuitas['total'];
        $totalLiabilitasModal = $totalLiabilitasPendek + $totalModal;

        return [
            'today' => $today,
            'kasBank' => $kasBank,
            'piutang' => $piutang,
            'persediaan' => $persediaan,
            'aktivaLancarLain' => $aktivaLancarLain,
            'asetTetap' => $asetTetap,
            'depresiasi' => $depresiasi,
            'hutang' => $hutang,
            'kewajibanLancar' => $kewajibanLancar,
            'ekuitas' => $ekuitas,
            'totalAsetLancar' => $totalAsetLancar,
            'totalAsetTetap' => $totalAsetTetap,
            'totalDepresiasi' => $totalDepresiasi,
            'totalAset' => $totalAset,
            'totalLiabilitasPendek' => $totalLiabilitasPendek,
            'totalModal' => $totalModal,
            'totalLiabilitasModal' => $totalLiabilitasModal,
        ];
    }
}
