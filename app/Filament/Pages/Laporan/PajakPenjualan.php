<?php

namespace App\Filament\Pages\Laporan;

use App\Models\SalesInvoice;
use App\Models\PurchaseInvoice;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class PajakPenjualan extends Page implements HasActions
{
    use InteractsWithActions;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected string $view = 'filament.pages.laporan.pajak-penjualan';

    protected static ?string $title = 'Pajak Penjualan';

    protected static ?string $slug = 'pajak-penjualan';

    protected static bool $shouldRegisterNavigation = false;

    public $startDate;
    public $endDate;
    public array $expandedRows = [];

    public function toggleRow($row): void
    {
        if (in_array($row, $this->expandedRows)) {
            $this->expandedRows = array_diff($this->expandedRows, [$row]);
        } else {
            $this->expandedRows[] = $row;
        }
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
            'Pajak Penjualan',
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
                ->icon('heroicon-m-calendar')
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
        $salesQuery = SalesInvoice::query()
            ->whereBetween('transaction_date', [$this->startDate, $this->endDate])
            ->where('status', '!=', 'cancelled');

        $salesTax = (clone $salesQuery)
            ->select(
                DB::raw('SUM(sub_total) as net'),
                DB::raw('SUM(total_tax) as tax')
            )
            ->first();

        $purchaseQuery = PurchaseInvoice::query()
            ->whereBetween('date', [$this->startDate, $this->endDate])
            ->where('status', '!=', 'cancelled');

        $purchaseTax = (clone $purchaseQuery)
            ->select(
                DB::raw('SUM(sub_total) as net'),
                DB::raw('SUM(tax_amount) as tax')
            )
            ->first();

        $salesDetails = [];
        if (in_array('sales', $this->expandedRows)) {
            $salesDetails = $salesQuery
                ->select('id', 'invoice_number as number', 'transaction_date as date', 'sub_total as net', 'total_tax as tax', 'total_amount as total')
                ->orderBy('transaction_date', 'desc')
                ->get();
        }

        $purchaseDetails = [];
        if (in_array('purchase', $this->expandedRows)) {
            $purchaseDetails = $purchaseQuery
                ->select('id', 'number', 'date', 'sub_total as net', 'tax_amount as tax', 'total_amount as total')
                ->orderBy('date', 'desc')
                ->get();
        }

        return [
            'sales' => $salesTax,
            'purchase' => $purchaseTax,
            'salesDetails' => $salesDetails,
            'purchaseDetails' => $purchaseDetails,
            'totalNet' => ($salesTax->net ?? 0) + ($purchaseTax->net ?? 0),
            'totalTax' => ($salesTax->tax ?? 0) - ($purchaseTax->tax ?? 0), // PPN Keluaran - PPN Masukan
        ];
    }
}
