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
    // use WithPagination; // Since we usually want to see all periods in the charts/table together, we might not need pagination or we handle it differently.

    protected string $view = 'filament.pages.laporan.pembelian-per-periode';

    protected static ?string $title = 'Pembelian per Periode';

    protected static ?string $slug = 'pembelian-per-periode';

    protected static bool $shouldRegisterNavigation = false;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    public ?string $startDate = null;
    public ?string $endDate = null;
    public string $periodType = 'monthly'; // daily, weekly, monthly, yearly

    public function mount(): void
    {
        $this->startDate = Carbon::now()->startOfYear()->format('Y-m-d');
        $this->endDate = Carbon::now()->format('Y-m-d');
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
        $dateFormat = match ($this->periodType) {
            'daily' => '%Y-%m-%d',
            'weekly' => '%Y-W%u',
            'monthly' => '%Y-%m',
            'yearly' => '%Y',
            default => '%Y-%m',
        };

        // Query for value and quantity aggregated by period
        $results = DB::table('purchase_invoices as pi')
            ->join('purchase_invoice_items as pii', 'pi.id', '=', 'pii.purchase_invoice_id')
            ->whereBetween('pi.date', [$this->startDate, $this->endDate])
            ->where('pi.status', '!=', 'cancelled')
            ->select(
                DB::raw("DATE_FORMAT(pi.date, '{$dateFormat}') as period"),
                DB::raw('SUM(pii.quantity) as total_qty'),
                DB::raw('SUM(pii.total_price) as total_value')
            )
            ->groupBy('period')
            ->orderBy('period', 'asc')
            ->get();

        // Format periods for display
        $formattedResults = $results->map(function ($item) {
            $periodLabel = $item->period;
            try {
                if ($this->periodType === 'monthly') {
                    $periodLabel = Carbon::createFromFormat('Y-m', $item->period)->translatedFormat('M Y');
                } elseif ($this->periodType === 'daily') {
                    $periodLabel = Carbon::parse($item->period)->translatedFormat('d M Y');
                } elseif ($this->periodType === 'yearly') {
                    $periodLabel = $item->period;
                }
            } catch (\Exception $e) {
                // Keep original if parsing fails
            }

            $item->period_label = $periodLabel;
            return $item;
        });

        return [
            'results' => $formattedResults,
            'grandTotalQty' => $formattedResults->sum('total_qty'),
            'grandTotalValue' => $formattedResults->sum('total_value'),
        ];
    }
}
