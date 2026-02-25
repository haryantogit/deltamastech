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
    public $perPage = 15;

    public function mount()
    {
        $this->startDate = Carbon::now()->startOfYear()->format('Y-m-d');
        $this->endDate = Carbon::now()->format('Y-m-d');
    }

    public function updatedStartDate(): void
    {
        $this->resetPage();
    }

    public function updatedEndDate(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
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

    public function getViewData(): array
    {
        $query = DB::table('sales_invoices as si')
            ->join('contacts as c', 'si.contact_id', '=', 'c.id')
            ->whereBetween('si.transaction_date', [$this->startDate, $this->endDate])
            ->where('si.status', '!=', 'cancelled')
            ->select(
                DB::raw("COALESCE(NULLIF(c.province, ''), 'Lainnya') as region"),
                DB::raw('COUNT(si.id) as transaction_count'),
                DB::raw('SUM(si.total_amount) as total_amount')
            )
            ->groupBy('region')
            ->orderBy('total_amount', 'desc');

        $paginator = $query->paginate($this->perPage);

        // Global Totals for the footer
        $globalTotals = DB::table('sales_invoices as si')
            ->join('contacts as c', 'si.contact_id', '=', 'c.id')
            ->whereBetween('si.transaction_date', [$this->startDate, $this->endDate])
            ->where('si.status', '!=', 'cancelled')
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
