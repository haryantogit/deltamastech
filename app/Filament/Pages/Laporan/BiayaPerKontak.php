<?php

namespace App\Filament\Pages\Laporan;

use App\Models\Expense;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class BiayaPerKontak extends Page implements HasActions
{
    use InteractsWithActions;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';

    protected string $view = 'filament.pages.laporan.biaya-per-kontak';

    protected static ?string $title = 'Biaya per Kontak';

    protected static ?string $slug = 'biaya-per-kontak';

    protected static bool $shouldRegisterNavigation = false;

    public $startDate;
    public $endDate;
    public array $expandedRows = [];

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
                    $this->expandedRows = []; // Reset expansion on filter
                }),
            Action::make('ekspor')
                ->label('Ekspor')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('gray'),
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

        // Finalize summary data
        $summary = (clone $query)
            ->join('contacts', 'expenses.contact_id', '=', 'contacts.id')
            ->select(
                'contacts.id',
                'contacts.name',
                DB::raw('SUM(total_amount) as total_expenses')
            )
            ->groupBy('contacts.id', 'contacts.name')
            ->orderBy('total_expenses', 'desc')
            ->get();

        $details = [];
        foreach ($this->expandedRows as $contactId) {
            $details[$contactId] = (clone $query)
                ->where('contact_id', $contactId)
                ->select('id', 'reference_number as number', 'transaction_date as date', 'total_amount as total')
                ->orderBy('transaction_date', 'desc')
                ->get();
        }

        $totalAll = $summary->sum('total_expenses');

        // Chart Data (Top 10 + Others)
        $chartData = $summary->take(10);
        $others = $summary->slice(10);

        $chartLabels = $chartData->pluck('name')->toArray();
        $chartValues = $chartData->pluck('total_expenses')->toArray();

        if ($others->count() > 0) {
            $chartLabels[] = 'Lainnya';
            $chartValues[] = $others->sum('total_expenses');
        }

        return [
            'summary' => $summary,
            'details' => $details,
            'totalAll' => $totalAll,
            'chartLabels' => $chartLabels,
            'chartValues' => $chartValues,
        ];
    }
}
