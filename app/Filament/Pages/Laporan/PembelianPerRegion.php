<?php

namespace App\Filament\Pages\Laporan;

use App\Models\PurchaseInvoice;
use App\Models\Contact;
use Carbon\Carbon;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\Concerns\InteractsWithActions;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;

class PembelianPerRegion extends Page implements HasActions
{
    use InteractsWithActions;
    use WithPagination;

    protected static string $paginationView = 'filament-actions::link-pagination';

    protected string $view = 'filament.pages.laporan.pembelian-per-region';

    protected static ?string $title = 'Pembelian per Region';

    protected static ?string $slug = 'pembelian-per-region';

    protected static bool $shouldRegisterNavigation = false;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-map-pin';

    public ?string $startDate = null;
    public ?string $endDate = null;
    public ?string $search = null;
    public int $perPage = 15;

    public function mount(): void
    {
        $this->startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
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

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            \App\Filament\Pages\ReportPage::getUrl() => 'Laporan',
            'Pembelian per Region',
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
            Action::make('bagikan')
                ->label('Bagikan')
                ->icon('heroicon-o-share')
                ->color('gray'),
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

    public function getViewData(): array
    {
        $query = DB::table('purchase_invoices as pi')
            ->join('contacts as c', 'pi.supplier_id', '=', 'c.id')
            ->whereBetween('pi.date', [$this->startDate, $this->endDate])
            ->where('pi.status', '!=', 'cancelled')
            ->select(
                DB::raw("COALESCE(NULLIF(c.province, ''), 'Lainnya') as region"),
                DB::raw('COUNT(pi.id) as transaction_count'),
                DB::raw('SUM(pi.total_amount) as total_amount'),
                DB::raw('SUM(pi.total_amount) / COUNT(pi.id) as average_per_transaction')
            )
            ->groupBy('region')
            ->orderBy('total_amount', 'desc');

        if ($this->search) {
            $query->where('c.province', 'like', "%{$this->search}%");
        }

        $paginator = $query->paginate($this->perPage);

        // Global Totals
        $globalTotals = DB::table('purchase_invoices as pi')
            ->join('contacts as c', 'pi.supplier_id', '=', 'c.id')
            ->whereBetween('pi.date', [$this->startDate, $this->endDate])
            ->where('pi.status', '!=', 'cancelled')
            ->select(
                DB::raw('COUNT(pi.id) as total_count'),
                DB::raw('SUM(pi.total_amount) as total_amount')
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
