<?php

namespace App\Filament\Pages\Laporan;

use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use Carbon\Carbon;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\Concerns\InteractsWithActions;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;

class PembelianPerPeriode extends Page implements HasActions
{
    use InteractsWithActions;
    use WithPagination;

    protected string $view = 'filament.pages.laporan.pembelian-per-periode';

    protected static ?string $title = 'Pembelian per Periode';

    protected static ?string $slug = 'pembelian-per-periode';

    protected static bool $shouldRegisterNavigation = false;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    public ?string $startDate = null;
    public ?string $endDate = null;
    public $perPage = 10;
    public $search = '';

    protected $queryString = [
        'startDate' => ['except' => ''],
        'endDate' => ['except' => ''],
        'perPage' => ['except' => 10],
        'search' => ['except' => ''],
        'periodType' => ['except' => 'monthly'],
    ];

    public function mount(): void
    {
        $this->startDate = Carbon::now()->startOfYear()->format('Y-m-d');
        $this->endDate = Carbon::now()->format('Y-m-d');
    }

    public function getMaxContentWidth(): string
    {
        return 'full';
    }

    public function getSubheading(): \Illuminate\Contracts\Support\Htmlable|string|null
    {
        $startFmt = Carbon::parse($this->startDate)->format('d/m/Y');
        $endFmt = Carbon::parse($this->endDate)->format('d/m/Y');

        return new \Illuminate\Support\HtmlString('
            <div style="display: inline-flex; align-items: center; gap: 0.5rem; background-color: #f8fafc; padding: 0.5rem 1rem; border-radius: 0.5rem; border: 1px solid #e2e8f0; font-size: 0.875rem; font-weight: 600; color: #475569;" class="dark:bg-white/5 dark:border-white/10 dark:text-gray-300">
                <svg style="width: 1.25rem; height: 1.25rem; opacity: 0.7;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span>' . $startFmt . ' &mdash; ' . $endFmt . '</span>
            </div>
        ');
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            \App\Filament\Pages\ReportPage::getUrl() => 'Laporan',
            'Pembelian per Periode',
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
                    \Filament\Forms\Components\Select::make('periodType')
                        ->label('Tipe Periode')
                        ->options([
                            'daily' => 'Harian',
                            'weekly' => 'Mingguan',
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

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function getViewData(): array
    {
        $dateFormat = match ($this->periodType) {
            'daily' => '%Y-%m-%d',
            'weekly' => '%x-W%v', // ISO Year and Week
            'monthly' => '%Y-%m',
            'yearly' => '%Y',
            default => '%Y-%m',
        };

        // 1. Query for value and quantity aggregated by period
        $results = DB::table('purchase_invoices as pi')
            ->join('purchase_invoice_items as pii', 'pi.id', '=', 'pii.purchase_invoice_id')
            ->whereBetween('pi.date', [$this->startDate, $this->endDate])
            ->where('pi.status', '!=', 'cancelled')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('pi.invoice_number', 'like', "%{$this->search}%")
                        ->orWhere('pi.reference', 'like', "%{$this->search}%");
                });
            })
            ->select(
                DB::raw("DATE_FORMAT(pi.date, '{$dateFormat}') as period"),
                DB::raw('SUM(pii.quantity) as total_qty'),
                DB::raw('SUM(pii.total_price) as total_value')
            )
            ->groupBy('period')
            ->orderBy('period', 'asc')
            ->get()
            ->keyBy('period');

        // 2. Generate all periods in range for padding
        $allPeriods = collect();
        $start = Carbon::parse($this->startDate);
        $end = Carbon::parse($this->endDate);

        $current = $start->copy();
        while ($current <= $end) {
            $periodKey = match ($this->periodType) {
                'daily' => $current->format('Y-m-d'),
                'weekly' => $current->format('o-W'), // ISO Year-Week
                'yearly' => $current->format('Y'),
                default => $current->format('Y-m'),
            };

            if (!$allPeriods->has($periodKey)) {
                $label = match ($this->periodType) {
                    'daily' => $current->translatedFormat('d M Y'),
                    'weekly' => 'W' . $current->format('W') . ' ' . $current->format('Y'),
                    'yearly' => $current->format('Y'),
                    default => $current->translatedFormat('M Y'),
                };

                $data = $results->get($periodKey);

                $allPeriods->put($periodKey, (object) [
                    'period' => $periodKey,
                    'period_label' => $label,
                    'total_qty' => $data ? (float) $data->total_qty : 0,
                    'total_value' => $data ? (float) $data->total_value : 0,
                ]);
            }

            match ($this->periodType) {
                'daily' => $current->addDay(),
                'weekly' => $current->addWeek(),
                'yearly' => $current->addYear(),
                default => $current->addMonth(),
            };
        }

        $finalResults = $allPeriods->values();

        // Pagination for the table
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
            'grandTotalValue' => $finalResults->sum('total_value'),
        ];
    }
}
