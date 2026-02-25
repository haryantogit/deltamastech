<?php

namespace App\Filament\Pages\Laporan;

use App\Models\SalesDelivery;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class OngkosKirimEkspedisi extends Page implements \Filament\Actions\Contracts\HasActions
{
    use \Filament\Actions\Concerns\InteractsWithActions;
    use \Livewire\WithPagination;

    protected static ?string $navigationLabel = 'Ongkos Kirim per Ekspedisi';
    protected static ?string $title = 'Ongkos Kirim per Ekspedisi';
    protected static ?string $slug = 'ongkos-kirim-ekspedisi';
    protected string $view = 'filament.pages.laporan.ongkos-kirim-ekspedisi';
    protected static bool $shouldRegisterNavigation = false;

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-truck';
    }

    public $startDate;
    public $endDate;
    public $dateMode = 'transaksi'; // 'transaksi' or 'pengiriman'
    public $perPage = 15;
    public array $expandedCouriers = [];
    public array $expandedDates = [];

    protected $queryString = [
        'startDate',
        'endDate',
        'dateMode',
        'perPage',
    ];

    public function mount()
    {
        $this->startDate = Carbon::now()->startOfMonth()->toDateString();
        $this->endDate = Carbon::now()->endOfMonth()->toDateString();
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            \App\Filament\Pages\ReportPage::getUrl() => 'Laporan',
            'Ongkos Kirim per Ekspedisi',
        ];
    }

    public function updated($propertyName)
    {
        if (in_array($propertyName, ['dateMode', 'startDate', 'endDate', 'perPage'])) {
            $this->resetPage();
            $this->expandedCouriers = [];
            $this->expandedDates = [];
        }
    }

    public function updatedStartDate()
    {
        $this->resetPage();
    }

    public function updatedEndDate()
    {
        $this->resetPage();
    }

    public function toggleCourier(string $courierName)
    {
        if (in_array($courierName, $this->expandedCouriers)) {
            $this->expandedCouriers = array_diff($this->expandedCouriers, [$courierName]);
            // Also collapse all dates under this courier
            $this->expandedDates = array_filter($this->expandedDates, fn($key) => !str_starts_with($key, $courierName . '|'), ARRAY_FILTER_USE_KEY);
        } else {
            $this->expandedCouriers[] = $courierName;
        }
    }

    public function toggleDate(string $courierName, string $date)
    {
        $key = "{$courierName}|{$date}";
        if (isset($this->expandedDates[$key])) {
            unset($this->expandedDates[$key]);
        } else {
            $this->expandedDates[$key] = true;
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('filter')
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
                    $this->expandedCouriers = [];
                    $this->expandedDates = [];
                }),
            \Filament\Actions\Action::make('print')
                ->label('Print')
                ->color('gray')
                ->icon('heroicon-o-printer')
                ->action(fn() => $this->js('window.print()')),
            \Filament\Actions\Action::make('kembali')
                ->label('Kembali')
                ->color('gray')
                ->icon('heroicon-o-arrow-left')
                ->url(\App\Filament\Pages\ReportPage::getUrl()),
        ];
    }

    public function getViewData(): array
    {
        // Level 1: Couriers
        $query = DB::table('sales_deliveries as sd')
            ->leftJoin('sales_orders as so', 'sd.sales_order_id', '=', 'so.id')
            ->leftJoin('shipping_methods as sm', 'sd.shipping_method_id', '=', 'sm.id')
            ->where('sd.status', '!=', 'cancelled');

        if ($this->dateMode === 'transaksi') {
            $query->whereBetween('so.date', [$this->startDate, $this->endDate]);
        } else {
            $query->whereBetween('sd.date', [$this->startDate, $this->endDate]);
        }

        $query->select(
            DB::raw('COALESCE(sm.name, "Tanpa Ekspedisi") as courier_name'),
            DB::raw('COUNT(sd.id) as jumlah_pengiriman'),
            DB::raw('SUM(so.total_amount) as total_tagihan'),
            DB::raw('SUM(sd.shipping_cost) as total_ongkir')
        )->groupBy(DB::raw('COALESCE(sm.name, "Tanpa Ekspedisi")'))
            ->orderBy('courier_name');

        $paginator = $query->paginate($this->perPage);
        $totalCount = $paginator->total();
        $courierResults = $paginator->items();

        // Level 2 & 3: Dates and Invoices
        $results = [];
        foreach ($courierResults as $courier) {
            $courierData = (array) $courier;
            $courierData['dates'] = [];

            if (in_array($courier->courier_name, $this->expandedCouriers)) {
                $dateQuery = DB::table('sales_deliveries as sd')
                    ->leftJoin('sales_orders as so', 'sd.sales_order_id', '=', 'so.id')
                    ->leftJoin('shipping_methods as sm', 'sd.shipping_method_id', '=', 'sm.id')
                    ->where('sd.status', '!=', 'cancelled')
                    ->where(DB::raw('COALESCE(sm.name, "Tanpa Ekspedisi")'), $courier->courier_name);

                if ($this->dateMode === 'transaksi') {
                    $dateQuery->whereBetween('so.date', [$this->startDate, $this->endDate]);
                    $dateField = 'so.date';
                } else {
                    $dateQuery->whereBetween('sd.date', [$this->startDate, $this->endDate]);
                    $dateField = 'sd.date';
                }

                $dateResults = (clone $dateQuery)
                    ->select(
                        DB::raw("DATE($dateField) as date"),
                        DB::raw('COUNT(sd.id) as jumlah_pengiriman'),
                        DB::raw('SUM(so.total_amount) as total_tagihan'),
                        DB::raw('SUM(sd.shipping_cost) as total_ongkir')
                    )->groupBy(DB::raw("DATE($dateField)"))
                    ->orderBy('date', 'desc')
                    ->get();

                foreach ($dateResults as $dateRow) {
                    $dateData = (array) $dateRow;
                    $dateData['invoices'] = [];
                    $dateKey = "{$courier->courier_name}|{$dateRow->date}";

                    if (isset($this->expandedDates[$dateKey])) {
                        $invoiceResults = (clone $dateQuery)
                            ->leftJoin('contacts as c', 'so.customer_id', '=', 'c.id')
                            ->where(DB::raw("DATE($dateField)"), $dateRow->date)
                            ->select(
                                'so.number as invoice_number',
                                'c.name as customer_name',
                                'so.total_amount as total_tagihan',
                                'sd.shipping_cost as total_ongkir'
                            )
                            ->get();
                        $dateData['invoices'] = $invoiceResults->map(fn($item) => (array) $item)->toArray();
                    }
                    $courierData['dates'][] = $dateData;
                }
            }
            $results[] = $courierData;
        }

        // Global Totals
        $globalQuery = DB::table('sales_deliveries as sd')
            ->leftJoin('sales_orders as so', 'sd.sales_order_id', '=', 'so.id')
            ->where('sd.status', '!=', 'cancelled');

        if ($this->dateMode === 'transaksi') {
            $globalQuery->whereBetween('so.date', [$this->startDate, $this->endDate]);
        } else {
            $globalQuery->whereBetween('sd.date', [$this->startDate, $this->endDate]);
        }

        $globalTotals = $globalQuery->select(
            DB::raw('COUNT(sd.id) as grand_jumlah_pengiriman'),
            DB::raw('SUM(so.total_amount) as grand_total_tagihan'),
            DB::raw('SUM(sd.shipping_cost) as grand_total_ongkir')
        )->first();

        return [
            'results' => $results,
            'paginator' => $paginator,
            'totalCount' => $totalCount,
            'globalTotals' => [
                'jumlah_pengiriman' => $globalTotals->grand_jumlah_pengiriman ?? 0,
                'total_tagihan' => $globalTotals->grand_total_tagihan ?? 0,
                'total_ongkir' => $globalTotals->grand_total_ongkir ?? 0,
            ],
        ];
    }
}
