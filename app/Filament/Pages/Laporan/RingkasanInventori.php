<?php

namespace App\Filament\Pages\Laporan;

use App\Models\Product;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class RingkasanInventori extends Page implements HasActions
{
    use InteractsWithActions;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cube';

    protected string $view = 'filament.pages.laporan.ringkasan-inventori';

    protected static ?string $title = 'Ringkasan Inventori';

    protected static ?string $slug = 'ringkasan-inventori';

    protected static bool $shouldRegisterNavigation = false;

    public $date;
    public $search = '';

    public function mount()
    {
        $this->date = Carbon::now()->format('Y-m-d');
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            \App\Filament\Pages\ReportPage::getUrl() => 'Laporan',
            'Ringkasan Inventori',
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
                    \Filament\Forms\Components\DatePicker::make('date')
                        ->label('Per Tanggal')
                        ->default($this->date)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $this->date = $data['date'];
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
        // For inventory summary, it's usually current stock or at a point in time.
        // If it's "Per Tanggal", we might need to look at StockMovements, but the screenshot shows a simple list.
        // For now, let's fetch products with their current stock.

        $query = Product::query()
            ->with(['unit', 'category'])
            ->where('track_inventory', true)
            ->where('is_fixed_asset', false)
            ->where('is_active', true);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('sku', 'like', '%' . $this->search . '%');
            });
        }

        $products = $query->get()->map(function ($product) {
            $stock = $product->stock; // This is the total stock field on products table
            $hpp = $product->cost_of_goods;
            $value = $stock * $hpp;

            return (object) [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'stock' => $stock,
                'hpp' => $hpp,
                'value' => $value,
                'unit' => $product->unit_name ?? ($product->unit->name ?? 'Pcs'),
            ];
        });

        $totalQty = $products->sum('stock');
        $totalValue = $products->sum('value');

        return [
            'products' => $products,
            'totalQty' => $totalQty,
            'totalValue' => $totalValue,
        ];
    }
}
