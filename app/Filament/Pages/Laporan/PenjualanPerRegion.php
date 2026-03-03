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

class PenjualanPerRegion extends Page implements HasActions
{
    use InteractsWithActions;
    use WithPagination;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected string $view = 'filament.pages.laporan.penjualan-per-region';

    protected static ?string $title = 'Penjualan per Region';

    protected static ?string $slug = 'penjualan-per-region';

    protected static bool $shouldRegisterNavigation = false;

    public $startDate;
    public $endDate;
    public $search = '';
    public $perPage = 10;

    protected $queryString = [
        'startDate' => ['except' => ''],
        'endDate' => ['except' => ''],
        'perPage' => ['except' => 10],
        'search' => ['except' => ''],
    ];

    public function mount()
    {
        if (!$this->startDate) {
            $this->startDate = Carbon::now()->startOfYear()->format('Y-m-d');
        }
        if (!$this->endDate) {
            $this->endDate = Carbon::now()->format('Y-m-d');
        }
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function getSubheading(): \Illuminate\Contracts\Support\Htmlable|string|null
    {
        $startFmt = Carbon::parse($this->startDate)->format('d/m/Y');
        $endFmt = Carbon::parse($this->endDate)->format('d/m/Y');

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

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            \App\Filament\Pages\ReportPage::getUrl() => 'Laporan',
            'Penjualan per Region',
        ];
    }

    public function getMaxContentWidth(): string
    {
        return 'full';
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
                        ->label('Tanggal Mulai')
                        ->default($this->startDate)
                        ->required(),
                    \Filament\Forms\Components\DatePicker::make('endDate')
                        ->label('Tanggal Akhir')
                        ->default($this->endDate)
                        ->required(),
                ])
                ->action(function (array $data): void {
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
        $query = DB::table('sales_invoices as si')
            ->join('contacts as c', 'si.contact_id', '=', 'c.id')
            ->whereBetween('si.transaction_date', [$this->startDate, $this->endDate])
            ->where('si.status', '!=', 'cancelled')
            ->when($this->search, function ($q) {
                $q->where(DB::raw("COALESCE(NULLIF(c.province, ''), 'Lainnya')"), 'like', "%{$this->search}%");
            })
            ->select(
                DB::raw("COALESCE(NULLIF(c.province, ''), 'Lainnya') as region"),
                DB::raw('COUNT(si.id) as transaction_count'),
                DB::raw('SUM(si.total_amount) as total_amount')
            )
            ->groupBy('region')
            ->orderBy('total_amount', 'desc');

        $perPage = $this->perPage === 'all' ? max(1, $query->count()) : $this->perPage;
        $paginator = $query->paginate($perPage);

        // Global Totals for the footer
        $globalTotals = DB::table('sales_invoices as si')
            ->join('contacts as c', 'si.contact_id', '=', 'c.id')
            ->whereBetween('si.transaction_date', [$this->startDate, $this->endDate])
            ->where('si.status', '!=', 'cancelled')
            ->when($this->search, function ($q) {
                $q->where(DB::raw("COALESCE(NULLIF(c.province, ''), 'Lainnya')"), 'like', "%{$this->search}%");
            })
            ->select(
                DB::raw('COUNT(si.id) as total_count'),
                DB::raw('SUM(si.total_amount) as total_amount')
            )
            ->first();

        return [
            'results' => $paginator->items(),
            'paginator' => $paginator,
            'grandTotalCount' => $globalTotals->total_count ?? 0,
            'grandTotalAmount' => $globalTotals->total_amount ?? 0,
        ];
    }
}

