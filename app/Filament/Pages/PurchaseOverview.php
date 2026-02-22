<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Actions;
use Livewire\Attributes\Url;

class PurchaseOverview extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static string|\UnitEnum|null $navigationGroup = 'Pembelian';
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Ringkasan Pembelian';

    protected static ?string $slug = 'purchase-overview';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.purchase-overview';

    public function getMaxContentWidth(): string
    {
        return 'full';
    }

    #[Url]
    public string $filter = 'year';

    public function getHeaderWidgetsColumns(): int|array
    {
        return 12;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('cetak')
                ->label('Cetak')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->extraAttributes(['onclick' => 'window.print()'])
                ->button(),
            Actions\Action::make('bagikan')
                ->label('Bagikan')
                ->icon('heroicon-m-share')
                ->color('info')
                ->button(),
            Actions\ActionGroup::make([
                Actions\Action::make('bulan')
                    ->label('Bulan')
                    ->color(fn() => $this->filter === 'month' ? 'primary' : 'gray')
                    ->action(function () {
                        $this->filter = 'month';
                        $this->dispatch('update-purchase-overview-filter', filter: 'month');
                    }),
                Actions\Action::make('tahun')
                    ->label('Tahun')
                    ->color(fn() => $this->filter === 'year' ? 'primary' : 'gray')
                    ->action(function () {
                        $this->filter = 'year';
                        $this->dispatch('update-purchase-overview-filter', filter: 'year');
                    }),
            ])->label('Periode')
                ->icon('heroicon-m-calendar')
                ->color('gray')
                ->button(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\PurchaseOverviewStats::class,
            \App\Filament\Widgets\PurchaseOrderVsInvoiceChart::class,
            \App\Filament\Widgets\PurchasePaidRatioChart::class,
            \App\Filament\Widgets\PurchasePaymentSentChart::class,
            \App\Filament\Widgets\TopPurchasedProductsChart::class,
            \App\Filament\Widgets\PurchaseByWarehouseChart::class,
            \App\Filament\Widgets\TopSuppliersChart::class,
            \App\Filament\Widgets\PurchaseUnfinishedFlowWidget::class,
        ];
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            url('/admin/pembelian-page') => 'Pembelian',
            'Ringkasan',
        ];
    }
}
