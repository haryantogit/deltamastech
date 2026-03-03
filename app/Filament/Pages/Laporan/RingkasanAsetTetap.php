<?php

namespace App\Filament\Pages\Laporan;

use App\Models\Product;
use App\Models\FixedAssetDepreciation;
use App\Models\Category;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class RingkasanAsetTetap extends Page implements HasActions
{
    use InteractsWithActions;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office';

    protected string $view = 'filament.pages.laporan.ringkasan-aset-tetap';

    protected static ?string $title = 'Ringkasan Aset Tetap';

    protected static ?string $slug = 'ringkasan-aset-tetap';

    protected static bool $shouldRegisterNavigation = false;

    public $date;
    public $categoryId;
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
            'Ringkasan Aset Tetap',
        ];
    }

    public function getMaxContentWidth(): string
    {
        return 'full';
    }

    public function getSubheading(): \Illuminate\Contracts\Support\Htmlable|string|null
    {
        $date = $this->date ?? now()->toDateString();
        $dateFmt = \Carbon\Carbon::parse($date)->format('d/m/Y');

        return new \Illuminate\Support\HtmlString('
            <div style="display: inline-flex; align-items: center; gap: 0.5rem; background-color: #f8fafc; padding: 0.5rem 1rem; border-radius: 0.5rem; border: 1px solid #e2e8f0; font-size: 0.875rem; font-weight: 600; color: #475569;" class="dark:bg-white/5 dark:border-white/10 dark:text-gray-300">
                <svg style="width: 1.25rem; height: 1.25rem; opacity: 0.7;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2-2v12a2 2 0 002 2z" />
                </svg>
                <span>Per ' . $dateFmt . '</span>
            </div>
        ');
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
                    \Filament\Forms\Components\Select::make('categoryId')
                        ->label('Kategori Aset')
                        ->placeholder('Semua Kategori')
                        ->options(Category::pluck('name', 'id'))
                        ->default($this->categoryId),
                ])
                ->action(function (array $data): void {
                    $this->date = $data['date'];
                    $this->categoryId = $data['categoryId'];
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
        $targetDate = Carbon::parse($this->date);
        $period = $targetDate->format('Y-m');

        $query = Product::query()
            ->with(['category'])
            ->where('is_fixed_asset', true)
            ->where('status', 'registered')
            ->where('purchase_date', '<=', $targetDate->format('Y-m-d'));

        if ($this->categoryId) {
            $query->where('category_id', $this->categoryId);
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('sku', 'like', '%' . $this->search . '%');
            });
        }

        $assets = $query->get();

        // Group by category
        $grouped = $assets->groupBy(function ($asset) {
            return $asset->category->name ?? 'Tanpa Kategori';
        });

        $reportData = $grouped->map(function ($items, $categoryName) use ($period) {
            $mappedItems = $items->map(function ($asset) use ($period) {
                // Depreciation for this month
                $monthlyDepreciation = FixedAssetDepreciation::where('fixed_asset_id', $asset->id)
                    ->where('period', $period)
                    ->sum('amount');

                // If no entry for this month but it has depreciation, calculate it if possible?
                // Actually, the screenshot shows 7.911.667. 
                // Let's assume the user wants to see the monthly depreciation value.
                if ($monthlyDepreciation == 0 && $asset->has_depreciation && $asset->depreciation_rate > 0) {
                    $monthlyDepreciation = (float) $asset->purchase_price * ($asset->depreciation_rate / 100) / 12;
                }

                $bookValue = $asset->purchase_price - $asset->accumulated_depreciation_value;

                return (object) [
                    'id' => $asset->id,
                    'name' => $asset->name,
                    'number' => $asset->sku,
                    'reference' => $asset->reference ?? '-',
                    'purchase_date' => $asset->purchase_date,
                    'purchase_price' => (float) $asset->purchase_price,
                    'useful_life' => $asset->useful_life_years . ' Tahun',
                    'depreciation' => $monthlyDepreciation,
                    'book_value' => $bookValue,
                    'url' => \App\Filament\Resources\FixedAssetResource::getUrl('view', ['record' => $asset->id]),
                ];
            });

            return (object) [
                'name' => $categoryName,
                'items' => $mappedItems,
                'total_purchase' => $mappedItems->sum('purchase_price'),
                'total_depreciation' => $mappedItems->sum('depreciation'),
                'total_book_value' => $mappedItems->sum('book_value'),
            ];
        });

        return [
            'groupedData' => $reportData,
            'grand_total_purchase' => $reportData->sum('total_purchase'),
            'grand_total_depreciation' => $reportData->sum('total_depreciation'),
            'grand_total_book_value' => $reportData->sum('total_book_value'),
        ];
    }
}
