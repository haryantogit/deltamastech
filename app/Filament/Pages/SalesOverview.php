<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Actions;
use Livewire\Attributes\Url;

class SalesOverview extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static string|\UnitEnum|null $navigationGroup = 'Penjualan';
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Ringkasan Penjualan';

    protected static ?string $slug = 'sales-overview';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.sales-overview';

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
                        $this->dispatch('update-sales-overview-filter', filter: 'month');
                    }),
                Actions\Action::make('tahun')
                    ->label('Tahun')
                    ->color(fn() => $this->filter === 'year' ? 'primary' : 'gray')
                    ->action(function () {
                        $this->filter = 'year';
                        $this->dispatch('update-sales-overview-filter', filter: 'year');
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
            \App\Filament\Widgets\SalesOverviewStats::class,
            \App\Filament\Widgets\SalesOrderVsInvoiceChart::class,
            \App\Filament\Widgets\SalesPaidRatioChart::class,
            \App\Filament\Widgets\SalesPaymentReceivedChart::class,
            \App\Filament\Widgets\TopSellingProductsChart::class,
            \App\Filament\Widgets\SalesPersonPerformanceChart::class,
            \App\Filament\Widgets\TopCustomersChart::class,
            \App\Filament\Widgets\SalesUnfinishedFlowWidget::class,
        ];
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            url('/admin/penjualan-page') => 'Penjualan',
            'Ringkasan',
        ];
    }
}
