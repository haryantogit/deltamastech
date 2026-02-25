<?php

namespace App\Filament\Pages\Laporan;

use App\Models\Product;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Pages\Page;

class PelepasanAset extends Page implements HasActions
{
    use InteractsWithActions;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-trending-down';

    protected string $view = 'filament.pages.laporan.pelepasan-aset';

    protected static ?string $title = 'Pelepasan Aset';

    protected static ?string $slug = 'pelepasan-aset';

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
            'Pelepasan Aset',
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
            ->where('status', 'disposed')
            ->whereBetween('disposal_date', [$this->startDate, $this->endDate]);

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

        $reportData = $assets->map(function ($asset) {
            $cost = (float) $asset->purchase_price;
            $accumDep = (float) $asset->accumulated_depreciation_value;
            $bookValue = $cost - $accumDep;
            $salePrice = (float) $asset->disposal_price;
            $gainLoss = $salePrice - $bookValue;

            return (object) [
                'id' => $asset->id,
                'name' => $asset->name,
                'sku' => $asset->sku,
                'disposal_date' => $asset->disposal_date,
                'cost' => $cost,
                'accum_dep' => $accumDep,
                'book_value' => $bookValue,
                'sale_price' => $salePrice,
                'gain_loss' => $gainLoss,
                'url' => \App\Filament\Resources\FixedAssetResource::getUrl('view', ['record' => $asset->id]),
            ];
        });

        return [
            'assets' => $reportData,
            'total_cost' => $reportData->sum('cost'),
            'total_accum_dep' => $reportData->sum('accum_dep'),
            'total_book_value' => $reportData->sum('book_value'),
            'total_sale_price' => $reportData->sum('sale_price'),
            'total_gain_loss' => $reportData->sum('gain_loss'),
        ];
    }
}
