<?php

namespace App\Filament\Pages\Laporan;

use App\Models\SalesInvoice;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Livewire\WithPagination;

class PenjualanPerPeriode extends Page implements HasActions
{
    use InteractsWithActions;
    use WithPagination;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected string $view = 'filament.pages.laporan.penjualan-per-periode';

    protected static ?string $title = 'Penjualan per Periode';

    protected static ?string $slug = 'penjualan-per-periode';

    protected static bool $shouldRegisterNavigation = false;

    public $startDate;
    public $endDate;
    public $periodType = 'monthly'; // daily, monthly, quarterly, yearly
    public $search = '';
    public $perPage = 10;

    protected $queryString = [
        'startDate' => ['except' => ''],
        'endDate' => ['except' => ''],
        'periodType' => ['except' => 'monthly'],
        'search' => ['except' => ''],
        'perPage' => ['except' => 10],
    ];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function mount()
    {
        $this->startDate = Carbon::now()->startOfYear()->format('Y-m-d');
        $this->endDate = Carbon::now()->format('Y-m-d');
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            \App\Filament\Pages\ReportPage::getUrl() => 'Laporan',
            'Penjualan per Periode',
        ];
    }

    public function getMaxContentWidth(): string
    {
        return 'full';
    }

    public function getSubheading(): \Illuminate\Contracts\Support\Htmlable|string|null
    {
        $startFmt = Carbon::parse($this->startDate)->format('d/m/Y');
        $endFmt = Carbon::parse($this->endDate)->format('d/m/Y');

        $typeLabel = match ($this->periodType) {
            'daily' => 'Harian',
            'yearly' => 'Tahunan',
            default => 'Bulanan',
        };

        $dateDisplay = $startFmt === $endFmt
            ? $startFmt
            : $startFmt . ' &mdash; ' . $endFmt;

        return new \Illuminate\Support\HtmlString('
            <div style="display: inline-flex; align-items: center; gap: 0.5rem; background-color: #f8fafc; padding: 0.5rem 1rem; border-radius: 0.5rem; border: 1px solid #e2e8f0; font-size: 0.875rem; font-weight: 600; color: #475569;" class="dark:bg-white/5 dark:border-white/10 dark:text-gray-300">
                <svg style="width: 1.25rem; height: 1.25rem; opacity: 0.7;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span>' . $typeLabel . ' (' . $dateDisplay . ')</span>
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
                    \Filament\Forms\Components\Select::make('periodType')
                        ->label('Jenis Periode')
                        ->options([
                            'daily' => 'Harian',
                            'monthly' => 'Bulanan',
                            'yearly' => 'Tahunan',
                        ])
                        ->default($this->periodType)
                        ->required(),
                    \Filament\Forms\Components\DatePicker::make('startDate')
                        ->label('Tanggal Mulai')
                        ->default($this->startDate)
                        ->required(),
                    \Filament\Forms\Components\DatePicker::make('endDate')
                        ->label('Tanggal Akhir')
                        ->default($this->endDate)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $this->periodType = $data['periodType'];
                    $this->startDate = $data['startDate'];
                    $this->endDate = $data['endDate'];
                    $this->resetPage();
                }),
            Action::make('print')
                ->label('Print')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->action(fn() => $this->js('window.print()')),
            Action::make('kembali')
                ->label('Kembali')
                ->color('gray')
                ->icon('heroicon-o-arrow-left')
                ->url(\App\Filament\Pages\ReportPage::getUrl()),
        ];
    }

    public function getViewData(): array
    {
        // 1. Get query results
        $results = DB::table(function ($query) {
            $query->from('sales_invoices as si')
                ->join('sales_invoice_items as sii', 'si.id', '=', 'sii.sales_invoice_id')
                ->whereBetween('si.transaction_date', [$this->startDate, $this->endDate])
                ->where('si.status', '!=', 'cancelled')
                ->when($this->search, function ($query) {
                    $query->where(function ($q) {
                        $q->where('si.number', 'like', "%{$this->search}%")
                            ->orWhere('si.reference', 'like', "%{$this->search}%");
                    });
                })
                ->select(
                    'si.id',
                    'si.transaction_date',
                    'si.total_amount',
                    DB::raw('SUM(sii.qty) as invoice_qty')
                )
                ->groupBy('si.id', 'si.transaction_date', 'si.total_amount');
        }, 'agg')
            ->select(
                DB::raw(match ($this->periodType) {
                    'daily' => "DATE_FORMAT(transaction_date, '%Y-%m-%d')",
                    'yearly' => "DATE_FORMAT(transaction_date, '%Y')",
                    default => "DATE_FORMAT(transaction_date, '%Y-%m')",
                } . " as period"),
                DB::raw('SUM(invoice_qty) as total_qty'),
                DB::raw('SUM(total_amount) as total_amount')
            )
            ->groupBy('period')
            ->orderBy('period', 'asc')
            ->get()
            ->keyBy('period');

        // 2. Generate all periods in range
        $allPeriods = collect();
        $start = Carbon::parse($this->startDate);
        $end = Carbon::parse($this->endDate);

        $current = $start->copy();
        while ($current <= $end) {
            $periodKey = match ($this->periodType) {
                'daily' => $current->format('Y-m-d'),
                'yearly' => $current->format('Y'),
                default => $current->format('Y-m'),
            };

            if (!$allPeriods->has($periodKey)) {
                $label = match ($this->periodType) {
                    'daily' => $current->translatedFormat('d M Y'),
                    'yearly' => $current->format('Y'),
                    default => $current->translatedFormat('M Y'),
                };

                $data = $results->get($periodKey);

                $allPeriods->put($periodKey, (object) [
                    'period' => $periodKey,
                    'period_label' => $label,
                    'total_qty' => $data ? (float) $data->total_qty : 0,
                    'total_amount' => $data ? (float) $data->total_amount : 0,
                ]);
            }

            match ($this->periodType) {
                'daily' => $current->addDay(),
                'yearly' => $current->addYear(),
                default => $current->addMonth(),
            };
        }

        $finalResults = $allPeriods->values();

        // Pagination
        $currentPage = $this->getPage();
        $perPage = $this->perPage === 'all' ? max(1, $finalResults->count()) : $this->perPage;
        $paginatedResults = new \Illuminate\Pagination\LengthAwarePaginator(
            $finalResults->forPage($currentPage, $perPage),
            $finalResults->count(),
            $perPage,
            $currentPage,
            ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath()]
        );

        return [
            'results' => $paginatedResults,
            'chartResults' => $finalResults,
            'grandTotalQty' => $finalResults->sum('total_qty'),
            'grandTotalAmount' => $finalResults->sum('total_amount'),
        ];
    }
}
