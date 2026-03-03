<?php

namespace App\Filament\Pages\Laporan;

use App\Models\Product;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Pages\Page;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;

class RingkasanInventori extends Page implements HasActions
{
    use InteractsWithActions;
    use WithPagination;

    protected static string $paginationView = 'filament-actions::link-pagination';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cube';

    protected string $view = 'filament.pages.laporan.ringkasan-inventori';

    protected static ?string $title = 'Ringkasan Inventori';

    protected static ?string $slug = 'ringkasan-inventori';

    protected static bool $shouldRegisterNavigation = false;

    public $date;
    public $search = '';
    public $perPage = 10;

    public function mount()
    {
        $this->date = Carbon::now()->format('Y-m-d');
    }

    public function getSubheading(): \Illuminate\Contracts\Support\Htmlable|string|null
    {
        $dateFmt = Carbon::parse($this->date)->format('d/m/Y');

        return new \Illuminate\Support\HtmlString('
            <div style="display: inline-flex; align-items: center; gap: 0.5rem; background-color: #f8fafc; padding: 0.5rem 1rem; border-radius: 0.5rem; border: 1px solid #e2e8f0; font-size: 0.875rem; font-weight: 600; color: #475569;" class="dark:bg-white/5 dark:border-white/10 dark:text-gray-300">
                <svg style="width: 1.25rem; height: 1.25rem; opacity: 0.7;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span>' . $dateFmt . '</span>
            </div>
        ');
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

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedPerPage()
    {
        $this->resetPage();
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

        $allProducts = $query->get()->map(function ($product) {
            $stock = $product->stock;
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

        $totalQty = $allProducts->sum('stock');
        $totalValue = $allProducts->sum('value');

        // Pagination
        $perPage = $this->perPage === 'all' ? max(1, $allProducts->count()) : $this->perPage;
        $currentPage = \Illuminate\Pagination\Paginator::resolveCurrentPage();
        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $allProducts->forPage($currentPage, $perPage),
            $allProducts->count(),
            $perPage,
            $currentPage,
            ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath()]
        );

        return [
            'products' => $paginator->items(),
            'paginator' => $paginator,
            'totalQty' => $totalQty,
            'totalValue' => $totalValue,
        ];
    }
}
