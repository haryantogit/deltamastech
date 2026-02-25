<?php

namespace App\Filament\Pages\Laporan;

use App\Models\Product;
use App\Models\FixedAssetDepreciation;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class DetilAsetTetap extends Page implements HasActions
{
    use InteractsWithActions;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected string $view = 'filament.pages.laporan.detil-aset-tetap';

    protected static ?string $title = 'Detil Aset Tetap';

    protected static ?string $slug = 'detil-aset-tetap';

    protected static bool $shouldRegisterNavigation = false;

    public $startDate;
    public $endDate;
    public $categoryId;
    public $search = '';

    public function mount()
    {
        $this->startDate = '2026-01-01';
        $this->endDate = '2026-12-31';
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            \App\Filament\Pages\ReportPage::getUrl() => 'Laporan',
            'Detil Aset Tetap',
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
                    \Filament\Forms\Components\Select::make('categoryId')
                        ->label('Kategori Aset')
                        ->placeholder('Semua Kategori')
                        ->options(\App\Models\Category::pluck('name', 'id'))
                        ->default($this->categoryId),
                ])
                ->action(function (array $data): void {
                    $this->startDate = $data['startDate'];
                    $this->endDate = $data['endDate'];
                    $this->categoryId = $data['categoryId'];
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
        $query = Product::query()
            ->where('is_fixed_asset', true)
            ->where('status', 'registered');

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
        $startP = Carbon::parse($this->startDate)->format('Y-m');
        $endP = Carbon::parse($this->endDate)->format('Y-m');

        $reportData = $assets->map(function ($asset) use ($startP, $endP) {
            $ledger = new Collection();

            // 1. Initial Purchase (if within or before period)
            if ($asset->purchase_date <= $this->endDate) {
                $ledger->push((object) [
                    'date' => $asset->purchase_date,
                    'reference' => 'Initial Purchase of ' . $asset->name,
                    'debit' => (float) $asset->purchase_price,
                    'credit' => 0,
                    'sort_date' => $asset->purchase_date . ' 00:00:00',
                ]);
            }

            // 2. Depreciations filtered by period
            $depreciations = FixedAssetDepreciation::where('fixed_asset_id', $asset->id)
                ->where('period', '>=', $startP)
                ->where('period', '<=', $endP)
                ->get();

            foreach ($depreciations as $dep) {
                $displayDate = Carbon::parse($dep->period . '-01')->endOfMonth();
                $ledger->push((object) [
                    'date' => $displayDate,
                    'reference' => 'Depreciation on ' . $displayDate->format('d/m/Y'),
                    'debit' => 0,
                    'credit' => (float) $dep->amount,
                    'sort_date' => $displayDate->format('Y-m-d') . ' 23:59:59',
                ]);
            }

            // Filtrasi ledger berdasarkan range tanggal (jika ingin ketat)
            $filteredLedger = $ledger->filter(
                fn($row) =>
                Carbon::parse($row->date)->format('Y-m-d') >= $this->startDate &&
                Carbon::parse($row->date)->format('Y-m-d') <= $this->endDate
            )->sortBy('sort_date');

            return (object) [
                'id' => $asset->id,
                'name' => $asset->name,
                'sku' => $asset->sku,
                'ledger' => $filteredLedger,
                'total_debit' => $filteredLedger->sum('debit'),
                'total_credit' => $filteredLedger->sum('credit'),
            ];
        })->filter(fn($item) => $item->ledger->isNotEmpty());

        return [
            'reportData' => $reportData,
        ];
    }
}
