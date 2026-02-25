<?php

namespace App\Filament\Pages\Laporan;

use App\Models\ProductionOrder;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class LaporanProduksi extends Page implements HasActions
{
    use InteractsWithActions;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-beaker';

    protected string $view = 'filament.pages.laporan.laporan-produksi';

    protected static ?string $title = 'Laporan Produksi';

    protected static ?string $slug = 'laporan-produksi';

    protected static bool $shouldRegisterNavigation = false;

    public $startDate;
    public $endDate;
    public $search = '';

    public function mount()
    {
        $this->startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            \App\Filament\Pages\ReportPage::getUrl() => 'Laporan',
            'Laporan Produksi',
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
                        ->label('Tanggal Selesai')
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
        $ordersQuery = ProductionOrder::query()
            ->with(['product', 'warehouse', 'items', 'costs'])
            ->whereBetween('transaction_date', [$this->startDate . ' 00:00:00', $this->endDate . ' 23:59:59']);

        if ($this->search) {
            $ordersQuery->whereHas('product', function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('sku', 'like', '%' . $this->search . '%');
            });
        }

        $orders = $ordersQuery->orderBy('transaction_date', 'desc')->get();

        $rows = $orders->map(function ($order) {
            $materialCost = $order->items->sum('total_price');
            $otherCosts = $order->costs->sum('amount');
            $productionValue = $materialCost + $otherCosts;
            $quantity = (float) $order->quantity;
            $hpp = $quantity > 0 ? $productionValue / $quantity : 0;

            return (object) [
                'id' => $order->id,
                'finished_product' => $order->product->name ?? '-',
                'quantity' => $quantity,
                'hpp' => $hpp,
                'production_value' => $productionValue,
                'other_costs' => $otherCosts,
                'date' => $order->transaction_date,
                'number' => $order->number,
                'warehouse' => $order->warehouse->name ?? 'Unassigned',
                'tag' => $order->tag ?? '-',
                'url' => \App\Filament\Resources\ProductionOrderResource::getUrl('view', ['record' => $order->id]),
            ];
        });

        return [
            'rows' => $rows,
            'totalQty' => $rows->sum('quantity'),
            'totalProductionValue' => $rows->sum('production_value'),
            'totalOtherCosts' => $rows->sum('other_costs'),
        ];
    }
}
