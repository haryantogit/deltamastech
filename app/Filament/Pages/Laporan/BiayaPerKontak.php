<?php

namespace App\Filament\Pages\Laporan;

use App\Models\Expense;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Livewire\WithPagination;

class BiayaPerKontak extends Page implements HasActions
{
    use InteractsWithActions;
    use WithPagination;

    protected static string $paginationView = 'filament-actions::link-pagination';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';

    protected string $view = 'filament.pages.laporan.biaya-per-kontak';

    protected static ?string $title = 'Biaya per Kontak';

    protected static ?string $slug = 'biaya-per-kontak';

    protected static bool $shouldRegisterNavigation = false;

    public $startDate;
    public $endDate;
    public $search = '';
    public $perPage = 10;
    public array $expandedRows = [];

    protected $queryString = [
        'startDate' => ['except' => ''],
        'endDate' => ['except' => ''],
        'search' => ['except' => ''],
        'perPage' => ['except' => 10],
    ];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
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
            'Biaya per Kontak',
        ];
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
                ->extraAttributes(['class' => 'text-gray-600 [&>svg]:text-blue-500'])
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
                    $this->expandedRows = []; // Reset expansion on filter
                }),
            Action::make('print')
                ->label('Print')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->extraAttributes(['class' => 'text-gray-600 [&>svg]:text-blue-500'])
                ->action(fn() => $this->js('window.print()')),
            Action::make('kembali')
                ->label('Kembali')
                ->color('gray')
                ->icon('heroicon-o-arrow-left')
                ->extraAttributes(['class' => 'text-gray-600 [&>svg]:text-blue-500'])
                ->url(\App\Filament\Pages\ReportPage::getUrl()),
        ];
    }

    public function toggleRow($id): void
    {
        if (in_array($id, $this->expandedRows)) {
            $this->expandedRows = array_diff($this->expandedRows, [$id]);
        } else {
            $this->expandedRows[] = $id;
        }
    }

    public function getViewData(): array
    {
        $query = Expense::query()
            ->whereBetween('transaction_date', [$this->startDate, $this->endDate]);

        if (!empty($this->search)) {
            $query->whereHas('contact', function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%');
            })->orWhere('reference_number', 'like', '%' . $this->search . '%');
        }

        // Finalize summary data
        $summaryQuery = (clone $query)
            ->join('contacts', 'expenses.contact_id', '=', 'contacts.id')
            ->select(
                'contacts.id',
                'contacts.name',
                DB::raw('SUM(total_amount) as total_expenses')
            )
            ->groupBy('contacts.id', 'contacts.name')
            ->orderBy('total_expenses', 'desc');

        $allDataForChart = $summaryQuery->get();
        $totalAll = $allDataForChart->sum('total_expenses');
        $chartData = $allDataForChart->take(10);
        $others = $allDataForChart->slice(10);

        $chartLabels = $chartData->pluck('name')->toArray();
        $chartValues = $chartData->pluck('total_expenses')->toArray();

        if ($others->count() > 0) {
            $chartLabels[] = 'Lainnya';
            $chartValues[] = $others->sum('total_expenses');
        }

        $perPageCount = $this->perPage === 'all' ? max(1, (clone $summaryQuery)->get()->count()) : $this->perPage;
        $paginator = $summaryQuery->paginate($perPageCount);

        $details = [];
        foreach ($this->expandedRows as $contactId) {
            $details[$contactId] = clone $query
                ->where('contact_id', $contactId)
                ->select('id', 'reference_number as number', 'transaction_date as date', 'total_amount as total')
                ->orderBy('transaction_date', 'desc')
                ->get();
        }

        return [
            'paginator' => $paginator,
            'summary' => $paginator->items(),
            'details' => $details,
            'totalAll' => $totalAll,
            'chartLabels' => $chartLabels,
            'chartValues' => $chartValues,
        ];
    }
}
